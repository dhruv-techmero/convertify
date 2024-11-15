<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public static function create(Request $request)
    {
        // Step 1: Handle multiple file uploads
        $files = $request->file('images'); // This will give you an array of files
        $pdfFiles = []; // Array to hold the paths of generated PDFs
    
        foreach ($files as $file) {
            // Step 2: Ensure the file is a valid Word document (DOCX)
            $extension = $file->getClientOriginalExtension();
            if (!in_array(strtolower($extension), ['docx', 'doc'])) {
                return response()->json(['error' => 'Only DOCX files are supported.'], 400);
            }
    
            // Step 3: Save the file with a unique name
            $fileName = 'docToPdf' . '_' . uniqid() . '.' . $extension;
            $file->move(public_path('/uploads'), $fileName);
    
            // Get the full file path
            $filePath = public_path('/uploads') . '/' . $fileName;
    
            // Step 4: Load the Word document
            try {
                $content = \PhpOffice\PhpWord\IOFactory::load($filePath);
            } catch (\PhpOffice\PhpWord\Exception\Exception $e) {
                \Log::error('Error loading Word document: ' . $e->getMessage());
                return response()->json(['error' => 'Error loading document: ' . $e->getMessage()], 500);
            }
    
            // Step 5: Set up the PDF renderer (using DomPDF)
            $domPdfPath = base_path('vendor/dompdf/dompdf');
            \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
            \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF'); // Specify DomPDF
    
            // Step 6: Generate the PDF
            try {
                $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($content, 'PDF');
                $pdfFileName = time() . '_' . uniqid() . '.pdf'; // Unique PDF file name
                $pdfFilePath = public_path('/uploads') . '/' . $pdfFileName;
                $pdfWriter->save($pdfFilePath); // Save the generated PDF
    
                // Add the generated PDF file path to the list
                $pdfFiles[] = $pdfFilePath;
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error generating PDF: ' . $e->getMessage()], 500);
            }
        }
    
        // Step 7: Zip all the generated PDFs and return it to the user
        $zipFileName = 'docToPdf' . '_' . rand(000,999) . '_pdfs.zip';
        $zipFilePath = public_path('/uploads') . '/' . $zipFileName;
    
        // Create a ZIP archive
        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE) === TRUE) {
            foreach ($pdfFiles as $pdfFile) {
                $zip->addFile($pdfFile, basename($pdfFile)); // Add each PDF to the ZIP archive
            }
            $zip->close(); // Finalize the zip archive
        } else {
            return response()->json(['error' => 'Failed to create ZIP archive.'], 500);
        }
        session()->flash('toast', [
            'title' => 'Download Ready!',
            'message' => 'Your cropped PDFs are ready for download.',
            'status' => 'success', // or 'error', 'info', etc.
        ]);
        // Step 8: Return the ZIP archive for download
        return response()->download($zipFilePath, $zipFileName);
    }
    
}
