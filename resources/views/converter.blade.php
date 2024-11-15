<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Converter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link type="text/css" rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link type="text/css" rel="stylesheet" href="{{ asset('/website-assets/plugins/image-preview/src/image-uploader.css') }}">
    <link type="text/css" rel="stylesheet" href="{{ asset('/website-assets/plugins/toaster/css/bootstrap-toaster.css') }}">

    <style>
        .img-fluid {
            max-width: 100%;
            height: auto;
        }

        .list-group.list-group-numbered .list-group-item {
            border: none;
        }

        .list-group.list-group-numbered .list-group-item:before {
            position: absolute;
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

        /* Add more specificity to target the active tab */
        .nav-tabs .nav-item .nav-link.active {
            background-color: #E9F1FB !important;
            /* Custom color */
            color: #0A3C6B !important;
            /* Optional: Change the text color to maintain contrast */
        }

        /* Additional styles for hover and focus states */
        .nav-tabs .nav-item .nav-link:hover,
        .nav-tabs .nav-item .nav-link:focus {
            background-color: #E9F1FB !important;
            color: #0A3C6B !important;
        }

        .row h2 {
            font-size: 1.8em;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

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
        }

        .btn,
        .form-control {
            width: 100%;
        }

        @media (max-width: 768px) {
            .container {
                width: 100%;
            }

            .nav-tabs {
                font-size: 0.9em;
            }

            .col-7,
            .col-5 {
                margin-bottom: 15px;
                text-align: center;
            }

            .input-images {
                margin-bottom: 20px;
            }
        }

        @media (min-width: 768px) {
            .container {
                width: 75%;
            }
        }
    </style>
</head>

<body>
    @if ($errors->any())
    <script>
        window.validationErrors = @json($errors -> all());
    </script>
    @endif

    <div class="container mt-5 p-3">
        <div class="row mb-4">
            <!-- Image on left for larger screens -->
            <div class="col-12 col-md-7 mb-3 mb-md-0">
                <img id="conversion-image" src="{{ asset('website-assets/images/pdfTojpg.svg') }}" alt="" class="img-fluid">
            </div>

            <!-- Language dropdown aligned to the right -->
            <div class="col-12 col-md-5 text-center text-md-end">
                <div class="dropdown w-50 " style="float: right;">
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
                    <li class="list-group-item">@lang('text.steps.step1')</li>
                    <li class="list-group-item">@lang('text.steps.step2')</li>
                </ol>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item">
                <a class="nav-link" id="doc-to-pdf-tab" data-toggle="tab" href="#doc-to-pdf" role="tab" aria-controls="doc-to-pdf" aria-selected="false">DOC to PDF</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pdf-to-jpg-tab" data-toggle="tab" href="#pdf-to-jpg" role="tab" aria-controls="pdf-to-jpg" aria-selected="false">PDF to JPG</a>
            </li>
            <li class="nav-item">
                <a class="nav-link " id="jpg-to-pdf-tab" data-toggle="tab" href="#jpg-to-pdf" role="tab" aria-controls="jpg-to-pdf" aria-selected="true">JPG to PDF</a>
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
        </ul>

        <!-- Upload and Convert Section -->
        <div class="my-4">
            <form action="" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="input-images">
                    <input type="hidden" name="conversionType" id="conversionType">
                    <div class="input-images-1" style="padding-top: .5rem;"></div>
                </div>
                <div class="row text-center">
                    <div class="col-md-12">
                        <button id="convert-btn" class="btn btn-dark text-uppercase mt-3 w-50">Download all</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Information Section -->
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