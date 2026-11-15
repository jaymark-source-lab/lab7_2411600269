<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FitTrack Pro')</title>
</head>
<body style="margin:0; padding:0; background:#f4f7f7; font-family:'Segoe UI', Arial, sans-serif; color:#1f2a2c;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f7; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                       style="max-width:600px; background:#ffffff; border:1px solid #e7edee; border-radius:12px; overflow:hidden;">
                    {{-- header --}}
                    <tr>
                        <td style="background:#085054; color:#ffffff; padding:20px 28px; font-size:20px; font-weight:bold;">
                            FitTrack Pro
                            <span style="font-weight:normal; font-size:14px; color:#14a3ad;">&nbsp;·&nbsp;@yield('tag', 'Procurement')</span>
                        </td>
                    </tr>

                    {{-- body --}}
                    <tr>
                        <td style="padding:28px; font-size:15px; line-height:1.6;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- footer --}}
                    <tr>
                        <td style="background:#f4f7f7; padding:16px 28px; font-size:12px; color:#6b7b7c;">
                            This is an automated message from the FitTrack Pro inventory system.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>