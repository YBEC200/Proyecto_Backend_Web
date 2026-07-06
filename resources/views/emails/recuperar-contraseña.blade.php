@extends('emails.partials.layout')

@section('content')
    <p style="margin:0 0 12px 0;color:#6b7280;font-size:14px;letter-spacing:0.4px;text-transform:uppercase;">
        Recuperación de contraseña
    </p>

    <h2 style="margin:0 0 12px 0;color:#111827;font-size:24px;line-height:1.3;">
        ¡Hola, {{ $user->nombre }}!
    </h2>

    <p style="margin:0 0 14px 0;color:#4b5563;font-size:16px;line-height:26px;">
        Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en CDTECH. Si fuiste tú, puedes continuar haciendo clic en el botón de abajo.
    </p>

    <div style="text-align:center;margin:28px 0 24px 0;">
        <a href="{{ $enlace }}"
           style="background:#2764e7;color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:8px;display:inline-block;font-weight:bold;font-size:15px;">
            Restablecer contraseña
        </a>
    </div>

    <p style="margin:0 0 10px 0;color:#4b5563;font-size:15px;line-height:24px;">
        <strong>Nota de seguridad:</strong> Este enlace es temporal y solo será válido por un tiempo limitado. Si no realizaste esta solicitud, puedes ignorar este correo.
    </p>
@endsection
