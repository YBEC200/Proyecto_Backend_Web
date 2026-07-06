@extends('emails.partials.layout')

@section('content')
    <p style="margin:0 0 12px 0;color:#6b7280;font-size:14px;letter-spacing:0.4px;text-transform:uppercase;">
        Confirmación de compra
    </p>

    <h2 style="margin:0 0 8px 0;color:#111827;font-size:24px;line-height:1.3;">
        ¡Gracias por tu compra, {{ $user->nombre }}!
    </h2>

    <p style="margin:0 0 18px 0;color:#4b5563;font-size:15px;line-height:24px;">
        Orden <strong>#{{ $venta->Id }}</strong> · Fecha {{ $venta->Fecha->format('d/m/Y') }} · Método de pago {{ $venta->Metodo_Pago }}
    </p>

    <p style="margin:0 0 20px 0;color:#4b5563;font-size:16px;line-height:26px;">
        Tu pago ha sido procesado correctamente. A continuación, encontrarás el detalle de tu compra.
    </p>

    <table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:12px;border-collapse:collapse;">
        <thead>
            <tr style="border-bottom:2px solid #e5e7eb;">
                <th style="padding:10px 0;color:#111827;font-size:13px;text-align:left;">Producto</th>
                <th style="padding:10px 0;color:#111827;font-size:13px;text-align:center;">Cant.</th>
                <th style="padding:10px 0;color:#111827;font-size:13px;text-align:right;">Precio</th>
                <th style="padding:10px 0;color:#111827;font-size:13px;text-align:right;">Subtotal</th>
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
                    <td style="padding:12px 0;color:#232427;font-size:14px;">{{ $detail->product->nombre }}</td>
                    <td style="padding:12px 0;color:#232427;font-size:14px;text-align:center;">{{ $cantidad }}</td>
                    <td style="padding:12px 0;color:#232427;font-size:14px;text-align:right;">S/ {{ number_format($precioUnitario, 2) }}</td>
                    <td style="padding:12px 0;color:#232427;font-size:14px;text-align:right;">S/ {{ number_format($subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:16px;">
        <tr>
            <td width="60%"></td>
            <td width="40%">
                <table width="100%" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td style="padding:8px 0;color:#6b7280;font-size:14px;">Subtotal:</td>
                        <td style="padding:8px 0;color:#232427;font-size:14px;text-align:right;">S/ {{ number_format($totalGravada, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#6b7280;font-size:14px;">IGV (18%):</td>
                        <td style="padding:8px 0;color:#232427;font-size:14px;text-align:right;">S/ {{ number_format($totalIgv, 2) }}</td>
                    </tr>
                    <tr style="border-top:2px solid #e5e7eb;">
                        <td style="padding:12px 0;color:#111827;font-size:15px;font-weight:bold;">Total:</td>
                        <td style="padding:12px 0;color:#2764e7;font-size:17px;font-weight:bold;text-align:right;">S/ {{ number_format($venta->Costo_Total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="background:#f0f4ff;border-left:4px solid #2764e7;padding:14px 16px;margin:24px 0;border-radius:6px;">
        <p style="margin:0;color:#1f2937;font-size:14px;font-weight:bold;">
            📦 Tipo de entrega: {{ $venta->tipo_entrega }}
        </p>
        <p style="margin:6px 0 0 0;color:#6b7280;font-size:13px;">
            Estado: <strong>{{ $venta->estado }}</strong>
        </p>
    </div>

    @if(!empty($venta->enlace_pdf))
        <div style="text-align:center;margin:24px 0 10px 0;">
            <a href="{{ $venta->enlace_pdf }}"
            style="background:#2764e7;color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:8px;display:inline-block;font-weight:bold;font-size:15px;">
                Ver detalles de mi compra
            </a>
        </div>
    @endif

    <p style="margin:16px 0 0 0;color:#6b7280;font-size:13px;text-align:center;line-height:20px;">
        ¿Tienes dudas sobre tu pedido?<br>Contáctanos para ayudarte.
    </p>
@endsection