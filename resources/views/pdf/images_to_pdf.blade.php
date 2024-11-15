<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Images to PDF</title>
</head>
<body>
    @foreach ($imagePaths as $imagePath)
        <div style="text-align: center; margin-bottom: 20px;">
            <!-- Use file:// protocol to ensure the image is accessible in the PDF generation -->
            <img src="file://{{ $imagePath }}" style="max-width: 100%; height: auto;">
        </div>
    @endforeach
</body>
</html>