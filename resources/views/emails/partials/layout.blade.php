<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'CDTECH' }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial, Helvetica, sans-serif;">
    <div style="display:none;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#f3f4f6;">
        {{ $preview ?? 'Correo de CDTECH' }}
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td align="center" style="background:linear-gradient(135deg,#165aee 0%,#2f6df7 100%);padding:28px 32px;">
                            <img src="{{ config('app.url') }}/img/CDT.png" alt="Logo CDTECH" width="92" style="max-width:100%;display:block;border-radius:12px;background:#ffffff;padding:6px;">
                            <h1 style="margin:12px 0 0 0;color:#ffffff;font-size:24px;line-height:1.3;">
                                {{ $title ?? 'CDTECH' }}
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px 36px 24px 36px;">
                            @yield('content')
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="background:#f9fafb;padding:24px 32px;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;color:#4b5563;font-size:13px;line-height:20px;">
                                © {{ date('Y') }} CDTECH. Todos los derechos reservados.
                            </p>
                            <p style="margin:8px 0 0 0;color:#9ca3af;font-size:12px;line-height:18px;">
                                Este es un correo automático. No respondas a este mensaje.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
