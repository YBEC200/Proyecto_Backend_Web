
@extends('emails.partials.layout')

@section('content')
    <p style="margin:0 0 12px 0;color:#6b7280;font-size:14px;letter-spacing:0.4px;text-transform:uppercase;">
        Bienvenida
    </p>

    <h2 style="margin:0 0 12px 0;color:#111827;font-size:24px;line-height:1.3;">
        ¡Hola, {{ $user->nombre }}!
    </h2>

    <p style="margin:0 0 14px 0;color:#4b5563;font-size:16px;line-height:26px;">
        Gracias por unirte a CDTECH. Tu cuenta ha sido creada correctamente y ya puedes empezar a disfrutar de nuestros servicios.
    </p>

    <p style="margin:0 0 24px 0;color:#4b5563;font-size:16px;line-height:26px;">
        Tu cuenta está asociada al rol de <strong>{{ $user->rol }}</strong>.
    </p>

    <div style="text-align:center;margin:28px 0 24px 0;">
        <a href="{{ config('app.url') }}"
           style="background:#2764e7;color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:8px;display:inline-block;font-weight:bold;font-size:15px;">
            Iniciar sesión
        </a>
    </div>

    <div style="background:#f8fbff;border:1px solid #e2ebff;border-radius:10px;padding:16px 18px;margin-top:8px;">
        <p style="margin:0;color:#1f2937;font-size:14px;line-height:22px;">
            Si el botón no funciona, copia y pega esta dirección en tu navegador:
        </p>
        <p style="margin:6px 0 0 0;color:#2764e7;font-size:14px;word-break:break-all;">
            https://proyecto-frontend-mp0w271sf-ybecs-projects.vercel.app
        </p>
    </div>
@endsection
