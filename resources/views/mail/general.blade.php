<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $data['subject'] ?? 'Notification' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #eef2f7;
            font-family: Arial, Helvetica, sans-serif;
            color: #1f2937;
        }

        .email-wrapper {
            width: 100%;
            background-color: #eef2f7;
            padding: 24px 0;
        }

        .email-container {
            max-width: 640px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
        }

        /* Header */
        .email-header {
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            padding: 18px 22px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-logo img {
            max-width: 64px;
            height: auto;
            display: block;
            background: #ffffff;
            padding: 6px;
            border-radius: 6px;
        }

        .header-company {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 0.5px;
        }

        /* Subject */
        .email-subject {
            background-color: #f8fafc;
            padding: 18px 22px;
            font-size: 19px;
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Body */
        .email-body {
            padding: 22px;
            font-size: 15.5px;
            line-height: 1.7;
            color: #1f2937;
        }

        .email-body p {
            margin: 0 0 14px 0;
        }

        .highlight {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 12px 14px;
            border-radius: 4px;
            margin: 16px 0;
        }

        /* Footer */
        .email-footer {
            background-color: #f1f5f9;
            padding: 16px 22px;
            font-size: 13px;
            color: #475569;
            text-align: center;
        }

        .email-footer strong {
            color: #0f172a;
        }
    </style>
</head>
<body>

<div class="email-wrapper">
    <div class="email-container">

        <!-- HEADER -->
        <div class="email-header">
            <table class="header-table">
                <tr>
                    <td style="width:80px;">
                        @if(!empty($data['company_logo']))
                            <img src="{{ $data['company_logo'] }}" alt="Company Logo">
                        @endif
                    </td>
                    <td class="header-company">
                        {{ $data['company_name'] ?? 'Company Name' }}
                    </td>
                    <td style="width:80px;"></td>
                </tr>
            </table>
        </div>

        <!-- SUBJECT -->
        <div class="email-subject">
            {{ $data['subject'] ?? '' }}
        </div>

        <!-- BODY -->
        <div class="email-body">
            <p>
                Dear <strong>{{ $data['recipient_name'] ?? 'User' }}</strong>,
            </p>

            @if(!empty($data['message']))
                <div class="highlight">
                    {!! nl2br(e($data['message'])) !!}
                </div>
            @endif

            @if(!empty($data['extra_message']))
                <p>{!! nl2br(e($data['extra_message'])) !!}</p>
            @endif

            <p style="margin-top: 26px;">
                Regards,<br>
                <strong>{{ $data['company_name'] ?? 'Company Name' }}</strong><br>
                Contact No: {{ $data['company_contact'] ?? '' }}
            </p>
        </div>

        <!-- FOOTER -->
        <div class="email-footer">
            This is an automated email from
            <strong>{{ $data['company_name'] ?? 'Company Name' }}</strong>.<br>
            © {{ date('Y') }} {{ $data['company_name'] ?? 'Company Name' }}. All rights reserved.
        </div>

    </div>
</div>

</body>
</html>
