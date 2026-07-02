<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="background:#ffffff;border-radius:12px;overflow:hidden;max-width:600px;">
                    <tr>
                        <td align="center" style="background:#165aee;padding:20px 20px;">
                            <h1 style="margin:0;color:#ffffff;font-size:26px;">
                                Solicitud para restablecer tu contraseña
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:30px;">
                            <img
                                src="{{ config('app.url') }}/img/CDT.png"
                                alt="Logo"
                                width="100"
                                style="max-width:100%;display:block;border-radius:10px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 40px 30px 40px;">
                            <h2 style="color:#111827;">
                                ¡Hola, {{ $user->nombre }}!
                            </h2>
                            <p style="color:#232427;font-size:16px;line-height:28px;">
                                Recibimos una solicitud para restablecer la contraseña de tu cuenta en CDT.
                                Si fuiste tú, puedes continuar haciendo clic en el siguiente botón:
                            </p>

                            <div style="text-align:center;margin:35px 0;">
                                <a href="{{ $enlace }}"
                                   style="
                                        background:#2764e7;
                                        color:#ffffff;
                                        text-decoration:none;
                                        padding:15px 35px;
                                        border-radius:8px;
                                        display:inline-block;
                                        font-weight:bold;
                                        font-size:16px;">
                                    Restablecer Contraseña
                                </a>
                            </div>

                            <p style="color:#232427;font-size:15px;line-height:26px;">
                                <strong>Nota de seguridad:</strong> Este enlace de recuperación es válido por tiempo limitado. Si tú no solicitaste este cambio, ignora este correo de forma segura; tu contraseña seguirá siendo la misma.
                            </p>

                            <hr style="border:none;border-top:1px solid #e5e7eb;margin-top:30px;">

                            <p style="font-size:14px;color:#232427;">
                                Este es un correo automático. No respondas a este mensaje.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="background:#f9fafb;padding:30px;">
                            <p style="margin:0;color:#232427;font-size:14px;">
                                © 2026 CDT
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
