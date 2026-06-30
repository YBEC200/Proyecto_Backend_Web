<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="background:#ffffff;border-radius:12px;overflow:hidden;max-width:600px;">
                    <tr>
                        <td align="center" style="background:#165aee;padding:20px 20px;">
                            <h1 style="margin:0;color:#ffffff;font-size:26px;">
                                Solicitud para vinculación de correo electrónico
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:30px;">
                            <img
                                src="{{ asset('img/CDT.png') }}"
                                alt="Logo"
                                width="100"
                                style="max-width:100%;display:block;border-radius:10px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 40px 30px 40px;">
    
                            <h2 style="color:#111827;">
                                ¡Hola, {{ $nombre }}!
                            </h2>
                            <p style="color:#232427;font-size:16px;line-height:28px;">
                                Se ha solicitado la creación de una cuenta con su correo electrónico dentro de nuestra plataforma virtual de venta CDTECH.     
                            </p>
                            <p style="color:#232427;font-size:16px;line-height:28px;">
                                Si desea completar su registro, ingrese el siguiente código en la aplicación:
                            </p>
                            
                            <div style="text-align:center;margin:35px 0;">
                                <div style="
                                        background:#666565;
                                        color:#ffffff;
                                        padding:15px 35px;
                                        border-radius:8px;
                                        display:inline-block;
                                        font-weight:bold;
                                        font-size:22px;
                                        letter-spacing: 4px;">
                                    {{ $codigo }}
                                </div>
                            </div>
                            
                            <hr style="border:none;border-top:1px solid #e5e7eb;">
                            <p style="font-size:14px;color:#232427;">
                                Si tiene interés puede visitar nuestra página copiando y pegando este enlace en tu navegador:
                            </p>
                            <p style="font-size:14px;color:#2764e7;word-break:break-all;">
                                https://tu-frontend-react.com
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="background:#f9fafb;padding:30px;">
                            <p style="margin:0;color:#232427;font-size:14px;">
                                {{ date('Y') }} CDTECH. Todos los derechos reservados.
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