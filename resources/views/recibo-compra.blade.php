<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="background:#ffffff;border-radius:12px;overflow:hidden;max-width:600px;">
                    <tr>
                        <td align="center" style="background:#165aee;padding:20px 20px;">
                            <h1 style="margin:0;color:#ffffff;font-size:26px;">
                                ¡Confirmación de tu Compra! 🛒
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:25px;">
                            <img
                                src="img/CDT.png"
                                alt="Logo"
                                width="100"
                                style="max-width:100%;display:block;border-radius:10px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 40px 30px 40px;">
                            <h2 style="color:#111827;margin-bottom:5px;">
                                ¡Gracias por tu compra, {{ $user->nombre ?? 'Estimado Cliente' }}!
                            </h2>
                            <p style="color:#6b7280;font-size:14px;margin-top:0;margin-bottom:25px;">
                                Orden de compra: <strong>#{{ $venta->id ?? '10542' }}</strong> | Fecha: {{ now()->format('d/m/Y') }}
                            </p>
                            
                            <p style="color:#232427;font-size:16px;line-height:26px;">
                                Tu pago ha sido procesado con éxito a través de nuestra plataforma virtual **CDTECH**. A continuación, encontrarás el detalle de tu adquisición:
                            </p>
    
                            <table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:20px;border-collapse:collapse;">
                                <thead>
                                    <tr style="border-bottom:2px solid #e5e7eb;text-align:left;">
                                        <th style="padding:10px 0;color:#111827;font-size:14px;">Producto</th>
                                        <th style="padding:10px 0;color:#111827;font-size:14px;text-align:center;">Cant.</th>
                                        <th style="padding:10px 0;color:#111827;font-size:14px;text-align:right;">Precio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="border-bottom:1px solid #f3f4f6;">
                                        <td style="padding:12px 0;color:#232427;font-size:15px;">
                                            Teclado Mecánico RGB CDTECH G2000
                                        </td>
                                        <td style="padding:12px 0;color:#232427;font-size:15px;text-align:center;">1</td>
                                        <td style="padding:12px 0;color:#232427;font-size:15px;text-align:right;">$45.00</td>
                                    </tr>
                                    <tr style="border-bottom:1px solid #f3f4f6;">
                                        <td style="padding:12px 0;color:#232427;font-size:15px;">
                                            Mouse Gamer Óptico 3200 DPI
                                        </td>
                                        <td style="padding:12px 0;color:#232427;font-size:15px;text-align:center;">2</td>
                                        <td style="padding:12px 0;color:#232427;font-size:15px;text-align:right;">$30.00</td>
                                    </tr>
                                </tbody>
                            </table>
    
                            <table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:20px;">
                                <tr>
                                    <td width="60%"></td>
                                    <td width="40%">
                                        <table width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:14px;">Subtotal:</td>
                                                <td style="padding:6px 0;color:#232427;font-size:14px;text-align:right;">$75.00</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;color:#6b7280;font-size:14px;">IGV/Impuestos (18%):</td>
                                                <td style="padding:6px 0;color:#232427;font-size:14px;text-align:right;">$13.50</td>
                                            </tr>
                                            <tr style="border-top:1px solid #e5e7eb;">
                                                <td style="padding:10px 0;color:#111827;font-size:16px;font-weight:bold;">Total Paid:</td>
                                                <td style="padding:10px 0;color:#2764e7;font-size:18px;font-weight:bold;text-align:right;">$88.50</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
    
                            <div style="text-align:center;margin:35px 0 20px 0;">
                                <a href="https://tu-frontend-react.com/historial-compras"
                                   style="
                                        background:#2764e7;
                                        color:#ffffff;
                                        text-decoration:none;
                                        padding:13px 30px;
                                        border-radius:8px;
                                        display:inline-block;
                                        font-weight:bold;
                                        font-size:15px;">
                                    Ver Detalles en la Tienda
                                </a>
                            </div>
    
                            <hr style="border:none;border-top:1px solid #e5e7eb;margin-top:30px;">
                            <p style="font-size:14px;color:#6b7280;text-align:center;margin:0;">
                                ¿Tienes alguna duda con tu pedido? Contacta a soporte en cualquier momento.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="background:#f9fafb;padding:30px;">
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