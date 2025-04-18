<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>API Documentation - University Library System</title>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui.css" />
    <style>
        body {
            margin: 0;
            background: #fafafa;
        }
        .swagger-ui .topbar {
            background-color: #4f46e5;
            padding: 10px 0;
        }
        .swagger-ui .topbar .download-url-wrapper { display: none; }
        .swagger-ui .info .title {
            color: #4f46e5;
        }
        .swagger-ui .opblock.opblock-post {
            background: rgba(79, 70, 229, 0.1);
            border-color: #4f46e5;
        }
        .swagger-ui .opblock.opblock-post .opblock-summary-method {
            background: #4f46e5;
        }
        .swagger-ui .btn.execute {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }
        .swagger-ui .btn.execute:hover {
            background-color: #4338ca;
        }
        /* Make text more readable */
        .swagger-ui .opblock-summary-description,
        .swagger-ui .opblock-description-wrapper p,
        .swagger-ui .response-col_description__inner p {
            font-size: 14px;
            color: #374151;
        }
        /* Enhance parameter styling */
        .swagger-ui .parameters-col_description {
            font-size: 13px;
            color: #4B5563;
        }
        /* Better response styling */
        .swagger-ui .responses-inner h4,
        .swagger-ui .responses-inner h5 {
            font-size: 14px;
            color: #1F2937;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui-bundle.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "{{ url('/api/documentation.json') }}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                defaultModelsExpandDepth: -1,
                docExpansion: "list",
                persistAuthorization: true,
                filter: true,
                withCredentials: true
            });
            window.ui = ui;
        };
    </script>
</body>
</html> 