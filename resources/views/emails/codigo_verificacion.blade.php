@extends('emails.partials.layout')

@section('content')
    <p style="margin:0 0 12px 0;color:#6b7280;font-size:14px;letter-spacing:0.4px;text-transform:uppercase;">
        Verificación de cuenta
    </p>

    <h2 style="margin:0 0 12px 0;color:#111827;font-size:24px;line-height:1.3;">
        ¡Hola, {{ $nombre }}!
    </h2>

    <p style="margin:0 0 14px 0;color:#4b5563;font-size:16px;line-height:26px;">
        Hemos recibido una solicitud para crear o activar tu cuenta en CDTECH. Para continuar, ingresa el siguiente código de verificación en la aplicación:
    </p>

    <div style="text-align:center;margin:28px 0 24px 0;">
        <div style="display:inline-block;background:#111827;color:#ffffff;padding:14px 28px;border-radius:10px;font-size:24px;font-weight:bold;letter-spacing:4px;">
            {{ $codigo }}
        </div>
    </div>

    <p style="margin:0 0 16px 0;color:#4b5563;font-size:15px;line-height:24px;">
        Si no realizaste esta solicitud, puedes ignorar este mensaje.
    </p>

    <div style="background:#f8fbff;border:1px solid #e2ebff;border-radius:10px;padding:16px 18px;margin-top:8px;">
        <p style="margin:0;color:#1f2937;font-size:14px;line-height:22px;">
            También puedes visitar nuestra plataforma en:
        </p>
        <p style="margin:6px 0 0 0;color:#2764e7;font-size:14px;word-break:break-all;">
            https://proyecto-frontend-mp0w271sf-ybecs-projects.vercel.app
        </p>
    </div>
@endsection
