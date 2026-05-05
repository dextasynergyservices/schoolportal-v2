<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $schoolName }}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }
        a[x-apple-data-detectors] { color: inherit !important; text-decoration: none !important; font-size: inherit !important; }
        #MessageViewBody a { color: inherit; text-decoration: none; font-size: inherit; font-family: inherit; font-weight: inherit; line-height: inherit; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; margin: auto !important; }
            .fluid { max-width: 100% !important; height: auto !important; }
            .stack-on-mobile { display: block !important; width: 100% !important; max-width: 100% !important; }
            .center-on-mobile { text-align: center !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

    {{-- Email wrapper --}}
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="background-color: #f4f4f7; margin: 0; padding: 0;">
        <tr>
            <td align="center" style="padding: 30px 10px;">

                {{-- Main card --}}
                <table role="presentation" class="email-container" cellspacing="0" cellpadding="0" border="0" align="center" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">

                    {{-- Header with school branding --}}
                    <tr>
                        <td align="center" style="background-color: {{ $primaryColor }}; padding: 28px 40px;">
                            @if ($schoolLogoUrl)
                                <img src="{{ $schoolLogoUrl }}" alt="{{ $schoolName }}" width="120" height="60"
                                     style="display: block; margin: 0 auto; max-width: 200px; max-height: 80px; width: auto; height: auto; object-fit: contain;"
                                     class="fluid">
                                <p style="margin: 10px 0 0; font-size: 13px; color: rgba(255,255,255,0.85); letter-spacing: 0.5px;">{{ $schoolName }}</p>
                            @else
                                <p style="margin: 0; font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: -0.3px;">{{ $schoolName }}</p>
                            @endif
                        </td>
                    </tr>

                    {{-- Body content --}}
                    <tr>
                        <td style="padding: 40px 40px 30px; color: #374151; font-size: 15px; line-height: 1.7;">
                            {!! $body !!}
                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="padding: 0 40px;">
                            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 0;">
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding: 24px 40px 30px; color: #9ca3af; font-size: 12px; line-height: 1.6;">
                            <p style="margin: 0 0 4px;">
                                &copy; {{ date('Y') }} {{ $schoolName }}. {{ __('All rights reserved.') }}
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #d1d5db;">
                                {{ __('Powered by') }} <strong style="color: #6366f1;">DX-SchoolPortal</strong>
                            </p>
                        </td>
                    </tr>

                </table>
                {{-- /main card --}}

            </td>
        </tr>
    </table>

</body>
</html>
