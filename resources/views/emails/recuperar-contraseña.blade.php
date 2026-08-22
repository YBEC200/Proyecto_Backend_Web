@extends('emails.partials.layout')

@section('content')

    <p style="margin:0 0 10px 0;color:#6b7280;font-size:13px;letter-spacing:0.5px;text-transform:uppercase;">
        Seguridad de tu cuenta
    </p>

    <h2 style="margin:0 0 14px 0;color:#111827;font-size:24px;line-height:1.3;">
        ¡Hola, {{ $user->nombre }}!
    </h2>

    <p style="margin:0 0 16px 0;color:#4b5563;font-size:16px;line-height:26px;">
        Recibimos una solicitud para restablecer la contraseña de tu cuenta en
        <strong style="color:#111827;">CDTECH</strong>.
    </p>

    <p style="margin:0 0 24px 0;color:#4b5563;font-size:15px;line-height:24px;">
        Si realizaste esta solicitud, utiliza el siguiente botón para crear una
        nueva contraseña de forma segura.
    </p>


    <!-- BOTÓN PRINCIPAL -->

    <div style="text-align:center;margin:30px 0;">

        <a href="{{ $enlace }}"
           style="
                background:#2764e7;
                color:#ffffff;
                text-decoration:none;
                padding:14px 30px;
                border-radius:8px;
                display:inline-block;
                font-weight:bold;
                font-size:15px;
           ">
            🔐 Restablecer mi contraseña
        </a>

    </div>


    <!-- AVISO DE EXPIRACIÓN -->

    <table role="presentation"
           width="100%"
           cellspacing="0"
           cellpadding="0"
           border="0"
           style="
                background:#eff6ff;
                border:1px solid #bfdbfe;
                border-radius:10px;
                margin:0 0 26px 0;
           ">

        <tr>

            <td style="padding:16px 18px;">

                <p style="
                    margin:0;
                    color:#1e40af;
                    font-size:14px;
                    line-height:22px;
                ">
                    <strong>⏱ Este enlace es válido durante 1 hora.</strong>
                    <br>
                    Por seguridad, después de ese tiempo tendrás que solicitar
                    un nuevo enlace de recuperación.
                </p>

            </td>

        </tr>

    </table>


    <!-- PASOS -->

    <p style="
        margin:0 0 12px 0;
        color:#111827;
        font-size:16px;
        font-weight:bold;
    ">
        ¿Qué debes hacer?
    </p>


    <table role="presentation"
           width="100%"
           cellspacing="0"
           cellpadding="0"
           border="0"
           style="margin-bottom:24px;">

        <tr>

            <td valign="top"
                style="
                    width:30px;
                    color:#2764e7;
                    font-size:15px;
                    font-weight:bold;
                ">
                1.
            </td>

            <td style="
                color:#4b5563;
                font-size:14px;
                line-height:22px;
                padding-bottom:10px;
            ">
                Haz clic en <strong>“Restablecer mi contraseña”</strong>.
            </td>

        </tr>

        <tr>

            <td valign="top"
                style="
                    width:30px;
                    color:#2764e7;
                    font-size:15px;
                    font-weight:bold;
                ">
                2.
            </td>

            <td style="
                color:#4b5563;
                font-size:14px;
                line-height:22px;
                padding-bottom:10px;
            ">
                Se abrirá la página de recuperación de CDTECH.
            </td>

        </tr>

        <tr>

            <td valign="top"
                style="
                    width:30px;
                    color:#2764e7;
                    font-size:15px;
                    font-weight:bold;
                ">
                3.
            </td>

            <td style="
                color:#4b5563;
                font-size:14px;
                line-height:22px;
            ">
                Introduce y confirma tu nueva contraseña.
            </td>

        </tr>

    </table>


    <!-- SI EL BOTÓN NO FUNCIONA -->

    <div style="
        border-top:1px solid #e5e7eb;
        padding-top:20px;
        margin-top:10px;
    ">

        <p style="
            margin:0 0 8px 0;
            color:#6b7280;
            font-size:13px;
            line-height:20px;
        ">
            <strong>¿El botón no funciona?</strong>
        </p>

        <p style="
            margin:0;
            color:#9ca3af;
            font-size:12px;
            line-height:18px;
            word-break:break-all;
        ">
            Copia y pega el siguiente enlace en tu navegador:
        </p>

        <p style="
            margin:8px 0 0 0;
            color:#2764e7;
            font-size:12px;
            line-height:18px;
            word-break:break-all;
        ">
            {{ $enlace }}
        </p>

    </div>


    <!-- SEGURIDAD -->

    <table role="presentation"
           width="100%"
           cellspacing="0"
           cellpadding="0"
           border="0"
           style="
                background:#f9fafb;
                border:1px solid #e5e7eb;
                border-radius:10px;
                margin-top:26px;
           ">

        <tr>

            <td style="padding:16px 18px;">

                <p style="
                    margin:0 0 7px 0;
                    color:#111827;
                    font-size:14px;
                    font-weight:bold;
                ">
                    🛡️ Nota de seguridad
                </p>

                <p style="
                    margin:0;
                    color:#6b7280;
                    font-size:13px;
                    line-height:20px;
                ">
                    Si tú no solicitaste restablecer tu contraseña,
                    puedes ignorar este correo. Tu contraseña actual
                    permanecerá sin cambios.
                </p>

            </td>

        </tr>

    </table>

@endsection