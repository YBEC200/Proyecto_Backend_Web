<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="background:#ffffff;border-radius:12px;overflow:hidden;max-width:600px;">
                    
                    <!-- HEADER -->
                    <tr>
                        <td align="center" style="background:#165aee;padding:20px 20px;">
                            <h1 style="margin:0;color:#ffffff;font-size:26px;">
                                ¡Confirmación de tu Compra! 🛒
                            </h1>
                        </td>
                    </tr>

                    <!-- LOGO -->
                    <tr>
                        <td align="center" style="padding:25px;">
                            <img
                                src="{{ config('app.url') }}/img/CDT.png"
                                alt="Logo CDTECH"
                                width="100"
                                style="max-width:100%;display:block;border-radius:10px;">
                        </td>
                    </tr>

                    <!-- CONTENIDO PRINCIPAL -->
                    <tr>
                        <td style="padding:0 40px 30px 40px;">
                            
                            <h2 style="color:#111827;margin-bottom:5px;">
                                ¡Gracias por tu compra, {{ $user->nombre }}!
                            </h2>
                            <p style="color:#6b7280;font-size:14px;margin-top:0;margin-bottom:25px;">
                                Orden: <strong>#{{ $venta->Id }}</strong> | Fecha: {{ $venta->Fecha->format('d/m/Y') }} | Método: {{ $venta->Metodo_Pago }}
                            </p>
                            
                            <p style="color:#232427;font-size:16px;line-height:26px;">
                                Tu pago ha sido procesado con éxito. A continuación, encontrarás el detalle de tu compra:
                            </p>
    
                            <!-- TABLA DE PRODUCTOS -->
                            <table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:20px;border-collapse:collapse;">
                                <thead>
                                    <tr style="border-bottom:2px solid #e5e7eb;">
                                        <th style="padding:10px 0;color:#111827;font-size:14px;text-align:left;">Producto</th>
                                        <th style="padding:10px 0;color:#111827;font-size:14px;text-align:center;">Cantidad</th>
                                        <th style="padding:10px 0;color:#111827;font-size:14px;text-align:right;">Precio Unit.</th>
                                        <th style="padding:10px 0;color:#111827;font-size:14px;text-align:right;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalGravada = 0;
                                        $totalIgv = 0;
                                    @endphp
                                    
                                    @foreach($venta->details as $detail)
                                        @php
                                            $cantidad = $detail->Cantidad;
                                            $precioUnitario = (float)$detail->Costo;
                                            $valorUnitario = round($precioUnitario / 1.18, 2);
                                            $subtotal = round($valorUnitario * $cantidad, 2);
                                            $igv = round($subtotal * 0.18, 2);
                                            
                                            $totalGravada += $subtotal;
                                            $totalIgv += $igv;
                                        @endphp
                                        <tr style="border-bottom:1px solid #f3f4f6;">
                                            <td style="padding:12px 0;color:#232427;font-size:15px;">
                                                {{ $detail->product->nombre }}
                                            </td>
                                            <td style="padding:12px 0;color:#232427;font-size:15px;text-align:center;">
                                                {{ $cantidad }}
                                            </td>
                                            <td style="padding:12px 0;color:#232427;font-size:15px;text-align:right;">
                                                S/ {{ number_format($precioUnitario, 2) }}
                                            </td>
                                            <td style="padding:12px 0;color:#232427;font-size:15px;text-align:right;">
                                                S/ {{ number_format($subtotal, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
    
                            <!-- TOTALES -->
                            <table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:20px;">
                                <tr>
                                    <td width="60%"></td>
                                    <td width="40%">
                                        <table width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="padding:8px 0;color:#6b7280;font-size:14px;">Subtotal:</td>
                                                <td style="padding:8px 0;color:#232427;font-size:14px;text-align:right;">
                                                    S/ {{ number_format($totalGravada, 2) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:8px 0;color:#6b7280;font-size:14px;">IGV (18%):</td>
                                                <td style="padding:8px 0;color:#232427;font-size:14px;text-align:right;">
                                                    S/ {{ number_format($totalIgv, 2) }}
                                                </td>
                                            </tr>
                                            <tr style="border-top:2px solid #e5e7eb;">
                                                <td style="padding:12px 0;color:#111827;font-size:16px;font-weight:bold;">Total:</td>
                                                <td style="padding:12px 0;color:#2764e7;font-size:18px;font-weight:bold;text-align:right;">
                                                    S/ {{ number_format($venta->Costo_Total, 2) }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- INFO DE ENTREGA -->
                            <div style="background:#f0f4ff;border-left:4px solid #2764e7;padding:15px;margin:25px 0;border-radius:4px;">
                                <p style="margin:0;color:#1f2937;font-size:14px;font-weight:bold;">
                                    📦 Tipo de Entrega: {{ $venta->tipo_entrega }}
                                </p>
                                <p style="margin:5px 0 0 0;color:#6b7280;font-size:13px;">
                                    Estado: <strong>{{ $venta->estado }}</strong>
                                </p>
                            </div>

                            <!-- CTA -->
                            <div style="text-align:center;margin:25px 0;">
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
                                    Ver Detalles de tu Compra
                                </a>
                            </div>
    
                            <hr style="border:none;border-top:1px solid #e5e7eb;margin:30px 0;">
                            <p style="font-size:13px;color:#6b7280;text-align:center;margin:0;line-height:20px;">
                                ¿Tienes dudas sobre tu pedido?<br>
                                Contacta a nuestro equipo de soporte
                            </p>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td align="center" style="background:#f9fafb;padding:30px;">
                            <p style="margin:0;color:#232427;font-size:13px;">
                                © 2026 CDTECH. Todos los derechos reservados.
                            </p>
                            <p style="margin:8px 0 0 0;color:#9ca3af;font-size:12px;">
                                Este es un correo automático. No respondas a este mensaje.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>