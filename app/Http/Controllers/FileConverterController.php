<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\PdfToImage\Pdf as PdfToImage;
use PhpOffice\PhpWord\IOFactory;
use PDF;
use File;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File as FacadesFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Imagick;
use ZipStream\ZipStream;
use ZipStream\Option\Archive as ZipArchiveOption;
use Intervention\Image\Facades\Image;
use NcJoes\OfficeConverter\OfficeConverter;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
class FileConverterController extends Controller
{
    public function index(Request $request, $language = null)
    {
        $availableLanguages = User::language();  // Get available languages

        if (array_key_exists($request->language, $availableLanguages)) {
            Session::put('locale', $request->language);  // Store selected language in session
            App::setLocale($request->language);  // Optionally, change the locale for the application
        }else{
            App::setLocale('en'); 
        }


        if ($request->isMethod('POST')) {
            $conversionType = $request->input('conversionType');
            switch ($conversionType) {
                case 'pdfToDoc':
                    return $this->convertPdfToDoc($request);
                    break;
                case 'jpgTopdf':
                    return $this->convertJpgToPdf($request);
                    break;
                case 'pdfToJpg':
                    return $this->convertMultiplePdfsToJpg($request);
                    break;
                case 'pdfTopng':
                    return $this->convertMultiplePdfsToPng($request);
                    break;
                case 'pdfCompresser':
                    return $this->pdfCompress($request);
                    break;
                case 'docToPdf':
                    return  DocumentController::create($request);
                    break;
                case 'pngTopdf':
                    return $this->convertJpgToPdf($request);
                    break;
                case 'combinePdf':
                    return $this->combinePdfs($request);
                    break;
                case 'cropPdf':
                    return $this->cropPdf($request);
                    break;
            }
        }
        return view('converter');
    }

    public function docToPdf(Request $request)
    {
        $files = $request->file('images');
        $pdfFiles = [];
        $pdfFolderPath = public_path('pdfs');
    
        // Ensure the PDF folder exists
        if (!file_exists($pdfFolderPath)) {
            mkdir($pdfFolderPath, 0755, true);
            Log::info('Created directory: ' . $pdfFolderPath);
        }
    
        foreach ($files as $file) {
            try {
                $extension = $file->getClientOriginalExtension();
    
                if (in_array($extension, ['doc', 'docx'])) {
                    // Handle DOCX file (modern Word format)
                    if ($extension === 'docx') {
                        // Use PHPWord to handle DOCX files
                        $phpWord = IOFactory::load($file); // Load DOCX file
                        Log::info('PHPWord document loaded: ' . $file->getClientOriginalName());
    
                        // Convert DOCX to HTML first
                        $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
                        $tempHtmlFile = storage_path('app/temp/' . uniqid('temp_', true) . '.html');
                        $htmlWriter->save($tempHtmlFile);
    
                        // Load HTML content and convert to PDF
                        $htmlContent = file_get_contents($tempHtmlFile);
                        $pdf = PDF::loadHTML($htmlContent);
    
                        // Save PDF to the specified directory
                        $pdfFileName = uniqid('converted_', true) . '.pdf';
                        $pdfFilePath = $pdfFolderPath . DIRECTORY_SEPARATOR . $pdfFileName;
                        $pdf->save($pdfFilePath);
                        Log::info('PDF saved at: ' . $pdfFilePath);
    
                        $pdfFiles[] = $pdfFilePath;
                        unlink($tempHtmlFile);  // Clean up temporary HTML file after conversion
    
                    } else if ($extension === 'doc') {
                        // Handle DOC file (older Word format)
                        // Assuming LibreOffice is installed on your system
                        $tempDocPath = storage_path('app/temp/' . uniqid('temp_', true) . '.doc');
                        $file->move(storage_path('app/temp'), basename($tempDocPath));
                        
                        // Convert DOC to PDF using LibreOffice command
                        $convertedPdfPath = storage_path('app/temp/' . uniqid('converted_', true) . '.pdf');
                        $command = 'libreoffice --headless --convert-to pdf --outdir ' . dirname($convertedPdfPath) . ' ' . $tempDocPath;
                        exec($command); // Run LibreOffice command for conversion
                        Log::info('Converted DOC to PDF using LibreOffice: ' . $convertedPdfPath);
    
                        // Add the converted PDF to the list
                        $pdfFiles[] = $convertedPdfPath;
    
                        // Clean up the original DOC file after conversion
                        unlink($tempDocPath);
                    }
                } else {
                    Log::error("Invalid file type: " . $file->getClientOriginalName());
                }
            } catch (\Exception $e) {
                Log::error("Error converting DOC to PDF: " . $e->getMessage());
            }
        }
    
        // Create ZIP file for PDFs
        $zipFileName = 'converted_pdfs_' . time() . '.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);
        Log::info("Attempting to create ZIP file at: " . $zipFilePath);
    
        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== TRUE) {
            Log::error("Failed to create ZIP file at: " . $zipFilePath);
            return back()->with('toast', ['title' => 'Error!', 'message' => 'Something Went Wrong.']);
        }
    
        // Add PDFs to ZIP
        foreach ($pdfFiles as $pdfFile) {
            if (file_exists($pdfFile)) {
                $zip->addFile($pdfFile, basename($pdfFile));  // Add PDF to ZIP
                Log::info('Added PDF to ZIP: ' . $pdfFile);
            } else {
                Log::error("PDF file does not exist: " . $pdfFile);
            }
        }
    
        // Close the ZIP file
        $zip->close();
    
        // Clean up PDFs after adding to ZIP
        foreach ($pdfFiles as $pdfFile) {
            if (file_exists($pdfFile)) {
                unlink($pdfFile);  // Delete PDFs after adding to ZIP
                Log::info("Deleted PDF: " . $pdfFile);
            }
        }
    
        // dd(file_exists($zipFilePath));  // Debug if ZIP file was created
    
        // Return ZIP file for download
        if (file_exists($zipFilePath)) {
            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        } else {
            Log::error("ZIP file does not exist: " . $zipFilePath);
            return back()->with('toast', ['title' => 'Error!', 'message' => 'Something Went Wrong.']);
        }
    }
    
    

    public function cropPdf(Request $request)
    {
        // Validate the uploaded files
        $request->validate([
            'images.*' => 'required|file|mimes:pdf|max:10240', // 10MB max PDF for each file
        ], [
            'images.*.mimes' => 'Invalid Format',
        ]);
    
        // Get the uploaded files
        $files = $request->file('images');
    
        // Array to store output file paths for each cropped PDF
        $outputPdfPaths = [];
    
        foreach ($files as $file) {
            Log::info('Processing file: ' . $file->getClientOriginalName() . ' with MIME type: ' . $file->getMimeType());
    
            // Define the path for the uploaded file and output file
            $inputPdfPath = $file->getPathname(); // Temporary path of uploaded file
            $outputPdfPath = storage_path('app/public/cropped_output_' . uniqid() . '.pdf'); // Unique name for each cropped file
    
            // Initialize FPDI
            $pdf = new Fpdi();
    
            // Set the source PDF file
            $pageCount = $pdf->setSourceFile($inputPdfPath);
    
            // Loop through all pages and crop them
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                // Import a page
                $templateId = $pdf->importPage($pageNo);
    
                // Get the dimensions of the page (in points)
                $size = $pdf->getTemplateSize($templateId);
                Log::info('Page ' . $pageNo . ' size: width=' . $size['width'] . ', height=' . $size['height']);
    
                // Dynamically calculate the crop area based on the page size
                $cropX = 0;
                $cropY = 50; // Crop top 50px
                $cropWidth = $size['width']; // Full width
                $cropHeight = $size['height'] - 100; // Subtract top and bottom crop area
    
                // Make sure the crop height doesn't go below zero
                if ($cropHeight < 0) {
                    $cropHeight = 0;
                }
    
                // Add a page and use the imported template with the crop
                $pdf->addPage();
                $pdf->useTemplate($templateId, $cropX, $cropY, $cropWidth, $cropHeight);
            }
    
            // Output the cropped PDF to a file
            $pdf->Output('F', $outputPdfPath);
    
            // Add the output PDF path to the array
            $outputPdfPaths[] = $outputPdfPath;
        }
    
        // Create a ZIP file for all cropped PDFs
        $zip = new ZipArchive();
        $zipFileName = storage_path('app/public/cropped_pdfs.zip');
        if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
            foreach ($outputPdfPaths as $filePath) {
                $zip->addFile($filePath, basename($filePath));
            }
            $zip->close();
        }
    
        session()->flash('toast', [
            'title' => 'Download Ready!',
            'message' => 'Your cropped PDFs are ready for download.',
            'status' => 'success', // or 'error', 'info', etc.
        ]);
    
        // Return the ZIP file containing all cropped PDFs
        return response()->download($zipFileName, 'cropped_pdfs.zip')->deleteFileAfterSend(true);
    }
    

    public function convertMultiplePdfsToJpg(Request $request)
    {
        // Validate the uploaded PDF files
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'mimes:pdf|max:10240', // max 10MB for each file
        ],[
            'images.*.mimes' => 'Invalid Format'
        ]);

        $pdfFiles = $request->file('images'); // Multiple PDF files
        $zipPaths = []; // Store paths of generated ZIP files

        // Output directory for images (ensure this is writable)
        $outputDir = storage_path('app/public/converted_images');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Loop through each uploaded PDF file
        foreach ($pdfFiles as $pdfFile) {
            $pdfPath = $pdfFile->getPathname(); // Temporary path of the uploaded PDF file
            $baseFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
            // dd($baseFilename);
            $jpgPaths = []; // Array to store paths of the generated JPG files

            try {
                // Use a PDF to image converter library like Imagick, Spatie PDF to Image, etc.
                $pdf = new PdfToImage($pdfPath);
                $pdf->setOutputFormat('jpg');

                // Convert each page of the PDF to a JPG image
                $pdfPages = $pdf->getNumberOfPages();

                for ($i = 1; $i <= $pdfPages; $i++) {
                    // Create a path for the JPG file
                    $jpgFilePath = $outputDir . '/' . $baseFilename . "_page_" . $i . '.jpg';
                    $pdf->setPage($i);
                    $pdf->saveImage($jpgFilePath);
                    $jpgPaths[] = $jpgFilePath;
                }

                // Optionally: Zip the JPG images for this PDF
                $zip = new ZipArchive();
                $zipPath = $outputDir . '/' . $baseFilename . '_converted_images.zip';

                if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                    foreach ($jpgPaths as $jpg) {
                        $zip->addFile($jpg, basename($jpg));
                    }
                    $zip->close();
                }

                // Add this ZIP file path to the zipPaths array for the master zip
                $zipPaths[] = $zipPath;

                // Cleanup individual JPG files after zipping
                foreach ($jpgPaths as $jpg) {
                    unlink($jpg); // Delete the JPG image
                }
            } catch (\Exception $e) {
                // dd($e->getMessage());
                Log::error('Error converting PDF to JPG: ' . $e->getMessage());
                return back()->with('toast', [
                    'title' => 'Error!',
                    'message' => 'Something Went Wrong.'
                ]);
            }
        }

        // Create a master ZIP file for all converted PDF ZIP files
        if (!empty($zipPaths)) {
            $masterZip = new ZipArchive();
            $masterZipPath = $outputDir . '/master_converted_images.zip';

            if ($masterZip->open($masterZipPath, ZipArchive::CREATE) === TRUE) {
                foreach ($zipPaths as $zip) {
                    $masterZip->addFile($zip, basename($zip));
                }
                $masterZip->close();
            }

            // Cleanup individual ZIP files after creating the master zip
            foreach ($zipPaths as $zip) {
                unlink($zip); // Delete the individual zip
            }
            session()->flash('toast', [
                'title' => 'Download Ready!',
                'message' => 'Your  PDFs are ready for download.',
                'status' => 'success' // or 'error', 'info', etc.
            ]);

            return response()->download($masterZipPath); // Download the master ZIP
        }

        return back()->with('toast', [
            'title' => 'Error!',
            'message' => 'Something Went Wrong.'
        ]);
    }


    public function combinePdfs(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'mimes:pdf|max:10240', // max 10MB for each file
        ],[
            'images.*.mimes' => 'Invalid Format'
        ]);

        if ($request->hasFile('images')) {
            $pdfs = $request->file('images'); // Multiple PDF files
            $outputPdfPath = storage_path('app/public/combined.pdf'); // Path to save the combined PDF

            // Initialize FPDI
            $pdf = new Fpdi();

            foreach ($pdfs as $pdfFile) {
                // Get the path of the uploaded PDF
                $pdfPath = $pdfFile->getPathname();

                // Get the total number of pages in the PDF
                $pageCount = $pdf->setSourceFile($pdfPath);

                // Loop through each page of the current PDF and add it to the output PDF
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $pdf->importPage($pageNo); // Import the page
                    $pdf->addPage(); // Add a new page to the output PDF
                    $pdf->useTemplate($templateId); // Use the imported page in the output PDF
                }
            }

            // Save the combined PDF
            $pdf->Output('F', $outputPdfPath);

            session()->flash('toast', [
                'title' => 'Download Ready!',
                'message' => 'Your  PDFs are ready for download.',
                'status' => 'success' // or 'error', 'info', etc.
            ]);
            // Return the combined PDF as a download
            return response()->download($outputPdfPath);
        } else {
            return back()->with('toast', [
                'title' => 'Error!',
                'message' => 'Something Went Wrong.'
            ]);
        }
    }




    public function convertJpgToPdf(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'mimes:jpg,jpeg,png|max:10240', // max 10MB for each file
        ],[
            'images.*.mimes' => 'Invalid Format'
        ]);
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            $imagePaths = [];

            foreach ($images as $image) {
                $path = $image->store('converted_images', 'public');
                $imagePath = storage_path('app/public/converted_images/' . basename($path)); // Absolute path to image
                $imagePaths[] = $imagePath;
            }

            // Pass the image paths to the view
            $pdf = PDF::loadView('pdf.images_to_pdf', ['imagePaths' => $imagePaths]);

            session()->flash('toast', [
                'title' => 'Download Ready!',
                'message' => 'Your  PDFs are ready for download.',
                'status' => 'success' // or 'error', 'info', etc.
            ]);
            return $pdf->download('converted_images.pdf');
        } else {
            return back()->with('toast', [
                'title' => 'Error!',
                'message' => 'Something Went Wrong.'
            ]);
        }
    }


    protected function convertDocToHtml($phpWord)
    {
        try {
            $xmlWriter = IOFactory::createWriter($phpWord, 'HTML');
            $htmlContent = '';

            // Capture HTML output
            ob_start();
            $xmlWriter->save('php://output');
            $htmlContent = ob_get_clean();

            return $htmlContent;
        } catch (\Exception $e) {
            Log::error('Error converting DOC to HTML: ' . $e->getMessage());
            return '';
        }
    }


    public function pdfCompress(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'mimes:pdf,png,jpg,jpeg|max:10240', // max 10MB for each file
        ], [
            'images.*.mimes' => 'Invalid Format'
        ]);
    
        // Validate the uploaded files
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            $extention = $files[0]->getClientOriginalExtension();
            $compressedFiles = [];
            $zipFileName = 'compressed_'.$extention.'_'. time() . '.zip';
            $zipFilePath = storage_path('app/' . $zipFileName);
    
            // Create a new ZIP archive
            $zip = new ZipArchive;
            if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
                
                return response()->json(['message' => 'Could not create zip file.'], 500);
            }
    
            foreach ($files as $file) {
                $fileExtension = $file->getClientOriginalExtension();
    
                // If the file is a PDF, compress it using Ghostscript
                if ($fileExtension == 'pdf') {
                    $compressedFile = $this->compressPdf($file); // Compress the PDF
                    $compressedFiles[] = $compressedFile;
    
                    // Add the compressed file to the ZIP
                    $zip->addFile($compressedFile, basename($compressedFile));
                }
                // If the file is an image (jpeg, png), compress it using Intervention Image
                elseif (in_array($fileExtension, ['jpeg', 'jpg', 'png'])) {
                 $compressedImage =    $this->compressImage($file); // Implement this method as needed
                    $compressedImages[] = $compressedImage;
    
                    // Add the compressed file to the ZIP
                    $zip->addFile($compressedImage, basename($compressedImage));
                    
                }
            }
    
            // Close the ZIP file after adding all files
            $zip->close();
    
            // Return the ZIP file for download
            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        } else {
            return back()->with('toast', [
                'title' => 'Error!',
                'message' => 'No files uploaded.'
            ]);
        }
    }

    private function compressPdf($file)
    {
        $outputPath = storage_path('app/compressed-' . $file->getClientOriginalName());
        $inputPath = $file->getRealPath();
    
        // Escape file paths
        $escapedInputPath = escapeshellarg($inputPath);
        $escapedOutputPath = escapeshellarg($outputPath);
    
        // Ensure Ghostscript command is correct and handle paths safely
        $ghostscriptPath = "\"C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe\""; // Adjust path if needed
        $command = "{$ghostscriptPath} -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH -sOutputFile={$escapedOutputPath} {$escapedInputPath}";
    
        // Execute the command
        exec($command, $output, $returnCode);
    
        // If the command fails, log the output and return an error
        if ($returnCode !== 0) {
            \Log::error('Ghostscript compression failed', ['output' => $output, 'return_code' => $returnCode]);
            throw new \Exception("PDF compression failed with Ghostscript. Output: " . implode("\n", $output));
        }
    
        return $outputPath; // Return the path to the compressed file
    }
    
    private function compressImage($image)
    {
        $imagine = new Imagine();
        $outputPath = storage_path('app/public/compressed-' . $image->getClientOriginalName());
    
        // Open the image file
        $img = $imagine->open($image->getPathName());
    
        // Resize the image (you can specify the size or add other constraints)
        $img->resize(new Box(800, 600));
    
        // Save the image with compression (quality as a percentage)
        $img->save($outputPath, ['quality' => 75]); // Adjust quality
        // Return the path to the compressed image
        return $outputPath;
    }

 



    public function convertMultiplePdfsToPng(Request $request)
    {
        // Validate the uploaded PDF files
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'mimes:pdf|max:10240', // max 10MB for each file
        ],[
            'images.*.mimes' => 'Invalid Format'
        ]);

        $pdfFiles = $request->file('images'); // Multiple PDF files
        $zipPaths = []; // Store paths of generated ZIP files

        // Output directory for images (ensure this is writable)
        $outputDir = storage_path('app/public/converted_images');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Loop through each uploaded PDF file
        foreach ($pdfFiles as $pdfFile) {
            $pdfPath = $pdfFile->getPathname(); // Temporary path of the uploaded PDF file
            $baseFilename = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
            // dd($baseFilename);
            $jpgPaths = []; // Array to store paths of the generated JPG files

            try {
                // Use a PDF to image converter library like Imagick, Spatie PDF to Image, etc.
                $pdf = new PdfToImage($pdfPath);
                $pdf->setOutputFormat('png');

                // Convert each page of the PDF to a JPG image
                $pdfPages = $pdf->getNumberOfPages();

                for ($i = 1; $i <= $pdfPages; $i++) {
                    // Create a path for the JPG file
                    $pngFilePath = $outputDir . '/' . $baseFilename . "_page_" . $i . '.png';
                    $pdf->setPage($i);
                    $pdf->saveImage($pngFilePath);
                    $pngPaths[] = $pngFilePath;
                }

                // Optionally: Zip the JPG images for this PDF
                $zip = new ZipArchive();
                $zipPath = $outputDir . '/' . $baseFilename . '_converted_images.zip';

                if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                    foreach ($pngPaths as $png) {
                        $zip->addFile($png, basename($png));
                    }
                    $zip->close();
                }

                // Add this ZIP file path to the zipPaths array for the master zip
                $zipPaths[] = $zipPath;

                // Cleanup individual JPG files after zipping
                foreach ($pngPaths as $png) {
                    unlink($png); // Delete the JPG image
                }
            } catch (\Exception $e) {
                dd($e->getMessage());
                // Log::error('Error converting PDF to JPG: ' . $e->getMessage());
                return back()->with('toast', [
                    'title' => 'Error!',
                    'message' => 'Something Went Wrong.'
                ]);
            }
        }

        // Create a master ZIP file for all converted PDF ZIP files
        if (!empty($zipPaths)) {
            $masterZip = new ZipArchive();
            $masterZipPath = $outputDir . '/master_converted_images.zip';

            if ($masterZip->open($masterZipPath, ZipArchive::CREATE) === TRUE) {
                foreach ($zipPaths as $zip) {
                    $masterZip->addFile($zip, basename($zip));
                }
                $masterZip->close();
            }

            // Cleanup individual ZIP files after creating the master zip
            foreach ($zipPaths as $zip) {
                unlink($zip); // Delete the individual zip
            }

            session()->flash('toast', [
                'title' => 'Download Ready!',
                'message' => 'Your  PDFs are ready for download.',
                'status' => 'success' // or 'error', 'info', etc.
            ]);
            return response()->download($masterZipPath); // Download the master ZIP
        }

        return back()->with('toast', [
            'title' => 'Error!',
            'message' => 'Something Went Wrong.'
        ]);
    }
}
