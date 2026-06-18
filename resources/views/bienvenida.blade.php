
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f4f6;padding:40px 20px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                style="background:#ffffff;border-radius:12px;overflow:hidden;max-width:600px;">
                <tr>
                    <td align="center"
                        style="background:#165aee;padding:20px 20px;">
                        <h1 style="margin:0;color:#ffffff;font-size:30px;">
                            ¡Haz registrado correctamente tu correo!
                        </h1>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding:30px;">
                        <img
                            src="img/CDT.png"
                            alt="Logo"
                            width="100"
                            style="max-width:100%;display:block;border-radius:10px;">
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 40px 30px 40px;">

                        <h2 style="color:#111827;">
                            ¡Hola Estimado cliente, {{ $nombre }}!
                        </h2>
                        <p style="color:#232427;font-size:16px;line-height:28px;">
                            Gracias por confiar en nosotros. Queremos informarte que tu solicitud
                            ha sido procesada correctamente.       
                        </p>
                        <p style="color:#232427;font-size:16px;line-height:28px;">
                            Tu cuenta ha sido registrada con el rol de: <strong>{{ $user->rol }}</strong>
                        </p>
                        <div style="text-align:center;margin:35px 0;">
                            <a href="enlace"
                               style="
                                    background:#2764e7;
                                    color:#ffffff;
                                    text-decoration:none;
                                    padding:15px 35px;
                                    border-radius:8px;
                                    display:inline-block;
                                    font-weight:bold;
                                    font-size:16px;">
                                Iniciar Sesión
                            </a>
                        </div>
                        <hr style="border:none;border-top:1px solid #e5e7eb;">
                        <p style="font-size:14px;color:#232427;">
                            Si el botón no funciona ingrese a este enlace, copia y pega este enlace en tu navegador:
                        </p>
                        <p style="font-size:14px;color:#2764e7;word-break:break-all;">
                            enlace
                        </p>
                    </td>
                </tr>
                <tr>
                    <td
                        align="center"
                        style="background:#f9fafb;padding:30px;">
                        <p style="margin:0;color:#232427;font-size:14px;">
                            © 2026 CDT
                        </p>
                        <p style="margin-top:10px;color:#232427;font-size:13px;">
                            Este es un correo automático. No respondas a este mensaje.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
