<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JPG to PDF Converter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link type="text/css" rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

    <link type="text/css" rel="stylesheet" href="{{ asset('/website-assets/plugins/image-preview/src/image-uploader.css') }}">
    <link type="text/css" rel="stylesheet" href="{{ asset('/website-assets/plugins/toaster/css/bootstrap-toaster.css') }}">

</head>
<style>
    .list-group.list-group-numbered .list-group-item {
        border: none;
    }

    .list-group.list-group-numbered .list-group-item:before {
        /* content: counter(line); */
        position: absolute;
        /* top: 0; */
        /* left: 0; */
        left: -9px;
        font-size: 1.5em;
        border-right: 2px solid #F2F2F2;
        color: #9ca0a5;
        width: .9em;
        padding-right: 0.35em;
        text-align: right;
        line-height: 1.1;
        height: 100%;
    }

    /* Styling for headings */
    .row h2 {
        font-size: 1.8em;
        font-weight: bold;
        margin-bottom: 20px;
        color: #333;
    }

    /* Paragraph styling */
    .row p {
        font-size: 1em;
        line-height: 1.6;
        color: #555;
        margin-bottom: 15px;
    }

    .no-icons li {
        font-size: 1em;
        line-height: 1.6;
        color: #555;
        list-style: none;
        margin-bottom: 10px;
        position: relative;
        /* This is to allow customization of list items if needed */
    }
</style>

<body>
@if ($errors->any())
    <script>
        // Pass the validation errors to JavaScript
        window.validationErrors = @json($errors->all());
    </script>
@endif

    <div class="container mt-5 my-5" style="width: 75%;">
        <!-- Title -->
        <!-- <div class="text-center mb-4">
            <p class="lead">Easily combine multiple JPG images into a single PDF file.</p>
        </div> -->
        <div class="row">
            <div class="col-7 col-md-7 mb-3 mb-md-0">
                <img id="conversion-image" src="{{ asset('website-assets/images/pdfTojpg.svg') }}" alt="" class="img-fluid">
            </div>
            <div class="col-5 col-md-5 text-md-end">
                <div class="dropdown">
                    <button class="btn border border-1 dropdown-toggle" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        {{ \App\Models\User::language()[Request::get('language')] ?? 'Choose Language' }}
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="languageDropdown">
                        @foreach(\App\Models\User::language() as $code => $language)
                        <li>
                            <a class="dropdown-item {{ Request::get('language') == $code ? 'bg-primary text-white' : '' }}" href="{{ route('website-home', ['language' => $code]) }}">
                                {{ $language }}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>


            </div>
        </div>

        <div class="row p-3">
            <div class="col-12">
                <p>@lang('text.combine_images')</p>
                <ol class="p-2 list-group list-group-numbered">
                    <li class=" list-group-item">@lang('text.steps.step1')</li>
                    <li class="list-group-item">@lang('text.steps.step2')</li>
                </ol>

            </div>
        </div>

        <!-- Tabs Navigation -->
        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <!-- <li class="nav-item">
                <a class="nav-link active" id="pdf-to-doc-tab" data-toggle="tab" href="#pdf-to-doc" role="tab" aria-controls="pdf-to-doc" aria-selected="true">PDF to DOC</a>
            </li> -->
            <!-- <li class="nav-item">
                <a class="nav-link active" id="doc-to-pdf-tab" data-toggle="tab" href="#doc-to-pdf" role="tab" aria-controls="doc-to-pdf" aria-selected="true">DOC to PDF</a>
            </li> -->
            <li class="nav-item">
                <a class="nav-link" id="pdf-to-jpg-tab" data-toggle="tab" href="#pdf-to-jpg" role="tab" aria-controls="pdf-to-jpg" aria-selected="false">PDF to JPG</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="jpg-to-pdf-tab" data-toggle="tab" href="#jpg-to-pdf" role="tab" aria-controls="jpg-to-pdf" aria-selected="false">JPG to PDF</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pdf-to-png-tab" data-toggle="tab" href="#pdf-to-png" role="tab" aria-controls="pdf-to-png" aria-selected="false">PDF to PNG</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="png-to-pdf-tab" data-toggle="tab" href="#png-to-pdf" role="tab" aria-controls="png-to-pdf" aria-selected="false">PNG to PDF</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pdf-compresser-tab" data-toggle="tab" href="#pdf-compresser" role="tab" aria-controls="pdf-compresser" aria-selected="false">PDF COMPRESSER</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="combine-pdf-tab" data-toggle="tab" href="#combine-pdf" role="tab" aria-controls="combine-pdf" aria-selected="false">COMBINE PDF</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="crop-pdf-tab" data-toggle="tab" href="#crop-pdf" role="tab" aria-controls="crop-pdf" aria-selected="false">CROP PDF</a>
            </li>
            <!-- Add more tabs as needed -->
        </ul>

        <!-- Upload and Convert Section -->
        <div class=" mt-4">
            <form action="" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="input-images">
                    <input type="hidden" name="conversionType" id="conversionType">

                    <!-- <input type="file" id="file-upload" multiple class="form-control mb-3" /> -->
                    <div class="input-images-1" style="padding-top: .5rem;"></div>
                </div>
                <div class="row text-center">
                    <div class="col-md-12"> <button id="convert-btn" class="btn btn-dark text-uppercase mt-3">Download all</button></div>
                </div>
            </form>
        </div>
        <div class="row">
            <h2>@lang('text.crop_pdf_title')</h2>
            <p>@lang('text.crop_pdf_description')</p>
            <p>@lang('text.crop_pdf_mobile_description')</p>
            <p>@lang('text.crop_pdf_software_description')</p>
            <h2>@lang('text.crop_pdf_free_title')</h2>
            <p>@lang('text.crop_pdf_free_description')</p>

            <ul class="no-icons">
                <li>@lang('text.crop_pdf_free_steps.step1')</li>
                <li>@lang('text.crop_pdf_free_steps.step2')</li>
                <li>@lang('text.crop_pdf_free_steps.step3')</li>
                <li>@lang('text.crop_pdf_free_steps.step4')</li>
            </ul>

            <h2>@lang('text.pdf_safety_title')</h2>
            <p>@lang('text.pdf_safety_description')</p>
            <p>@lang('text.pdf_safety_assurance')</p>
        </div>

    </div>

    <!-- Bootstrap JS and dependencies -->
    <!-- <link type="text/css" rel="stylesheet" href=""> -->

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('/website-assets/plugins/image-preview/src/image-uploader.js') }}"></script>
    <script src="{{ asset('/website-assets/plugins/toaster/js/bootstrap-toaster.min.js') }}"></script>

    <script>
        Toast.setTheme(TOAST_THEME.DARK);

    $(document).ready(function() {
        // Define actions, including image paths, based on tab
        const tabActions = {
            "doc-to-pdf-tab": {
                acceptedFormats: ".doc,.docx",
                action: "docToPdf",
                image: "{{ asset('website-assets/images/docToPdf.svg') }}",
            },
            "pdf-to-jpg-tab": {
                acceptedFormats: ".pdf",
                action: "pdfToJpg",
                image: "{{ asset('website-assets/images/pdfToJpg.svg') }}",
            },
            "jpg-to-pdf-tab": {
                acceptedFormats: ".jpg",
                action: "jpgTopdf",
                image: "{{ asset('website-assets/images/jpgTopdf.svg') }}",
            },
            "pdf-to-png-tab": {
                acceptedFormats: ".pdf",
                action: "pdfTopng",
                image: "{{ asset('website-assets/images/pdfTopng.svg') }}",
            },
            "png-to-pdf-tab": {
                acceptedFormats: ".png",
                action: "pngTopdf",
                image: "{{ asset('website-assets/images/pngTopdf.svg') }}",
            },
            "pdf-compresser-tab": {
                acceptedFormats: ".pdf",
                action: "pdfCompresser",
                image: "{{ asset('website-assets/images/pdfCompresser.svg') }}",
            },
            "combine-pdf-tab": {
                acceptedFormats: ".pdf",
                action: "combinePdf",
                image: "{{ asset('website-assets/images/combinePdf.svg') }}",
            },
            "crop-pdf-tab": {
                acceptedFormats: ".pdf",
                action: "cropPdf",
                image: "{{ asset('website-assets/images/cropPdf.svg') }}",
            },
            // Add other tab mappings as needed
        };

        // Set default for the first tab
        updateUploadSettings("pdf-to-doc-tab");

        // Update settings based on the selected tab
        $('.nav-link').on('click', function() {
            const tabId = $(this).attr('id');
            updateUploadSettings(tabId);
        });

        // Function to update upload settings and image
        function updateUploadSettings(tabId) {
            const actionDetails = tabActions[tabId];

            if (actionDetails) {
                $('#file-upload').attr('accept', actionDetails.acceptedFormats);
                $('#convert-btn').data('action', actionDetails.action);
                $('#conversion-image').attr('src', actionDetails.image); // Update the image src
                $('#conversionType').val(actionDetails.action);
            }
        }

        // Handle Convert button click
        $('#convert-btn').on('click', function() {
            
            setTimeout(function() {
        location.reload();  // This will reload the current page
    }, 5000);
            const action = $(this).data('action');
            const files = $('#file-upload')[0].files;

            if (files.length === 0) {
                alert("Please upload files before converting.");
                return;
            }

            // Validate file types
            const acceptedFormats = tabActions[$('.nav-link.active').attr('id')].acceptedFormats.split(',');
            const invalidFiles = Array.from(files).filter(file => {
                return !acceptedFormats.some(format => file.name.toLowerCase().endsWith(format));
            });

            if (invalidFiles.length > 0) {
                // Create a list of invalid file names
                const invalidFileNames = invalidFiles.map(file => file.name).join(', ');
                
                // Display toast notification
                // Toast.create({
                //     title: "Invalid File Type",
                //     message: `Invalid file(s): ${invalidFileNames}. Please upload files in the correct format: ${acceptedFormats.join(', ')}`,
                //     status: 'error',
                //     timeout: 3000
                // });

                // Remove invalid files from the file input
                const validFiles = Array.from(files).filter(file => !invalidFiles.includes(file));
                $('#file-upload')[0].files = new FileListItems(validFiles); // This is not directly supported in JavaScript, so we must handle it carefully.

                // Alternative: Manually clear the file input and add the valid files back (workaround for the limitation)
                let dataTransfer = new DataTransfer();
                validFiles.forEach(file => dataTransfer.items.add(file));
                $('#file-upload')[0].files = dataTransfer.files;

                return;
            }

            // Placeholder switch for different actions
            switch (action) {
                case "pdfToDoc":
                    console.log("Converting PDF to DOC");
                    break;
                case "docToPdf":
                    console.log("Converting DOC to PDF");
                    break;
                case "pdfToJpg":
                    console.log("Converting PDF to JPG");
                    break;
                    // Add additional cases as needed
            }


        });

        // Initialize the image uploader
        $('.input-images-1').imageUploader({});

      
    });
    $(document).ready(function() {
    // Check if there are validation errors passed from the server
    if (window.validationErrors && window.validationErrors.length > 0) {
        // Loop through the validation errors and show each one in a toast
        window.validationErrors.forEach(function(errorMessage) {
            Toast.create({
                title: 'Validation Error',
                message: errorMessage,
                status: 'error',
                timeout: 5000
            });
        });
    }

    // Handle success message from session if available
    let toastConfig = @json(session('toast'));
    if (toastConfig) {
        Toast.create({
            title: toastConfig.title,
            message: toastConfig.message,
            status: toastConfig.status || 'success',
            timeout: toastConfig.timeout || 3000
        });
    }
});

</script>


</body>

</html>