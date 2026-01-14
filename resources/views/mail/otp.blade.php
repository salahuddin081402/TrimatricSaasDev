<!DOCTYPE html>
<html lang="en">
    <head>
        <title>{{ $data['title'] }}</title>
        <style>
            .otp-wrapper {
                background-color: #ddd;
                padding: 5px;
            }

            .otp-wrapper p {
                color: #000;
            }
        </style>
    </head>
    <body>
        <div class="otp-wrapper">
            <p>{{ $data['message'] }}</p>
        </div>
    </body>
</html>