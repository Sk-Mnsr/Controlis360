<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de votre mot de passe</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="height:4px;background:#c00000;font-size:0;line-height:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:28px 32px 8px;">
                            <img src="{{ $message->embed(public_path('logo_Cofina.png')) }}" alt="COFINA" width="140" style="display:block;margin:0 auto;height:auto;max-width:140px;border:0;">
                            <p style="margin:14px 0 0;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#c00000;">Controlis360</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 32px 28px;">
                            <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;font-weight:700;color:#0f172a;">Réinitialisation de votre mot de passe</h1>
                            <p style="margin:0 0 12px;font-size:15px;line-height:1.6;color:#334155;">Bonjour <strong>{{ $recipient->name }}</strong>,</p>
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#334155;">
                                @if($sender)
                                    <strong>{{ $sender->name }}</strong> a réinitialisé votre mot de passe Controlis360.
                                @else
                                    Votre mot de passe Controlis360 a été réinitialisé.
                                @endif
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;">Mot de passe temporaire</p>
                                        <p style="margin:0;font-size:20px;font-weight:700;letter-spacing:0.02em;color:#0f172a;">{{ $plainPassword }}</p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:18px 0 24px;font-size:14px;line-height:1.6;color:#475569;">À la prochaine connexion, vous devrez choisir un nouveau mot de passe avant d'accéder à l'application.</p>
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-radius:8px;background:#c00000;">
                                        <a href="{{ $loginUrl }}" style="display:inline-block;padding:12px 22px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">Se connecter</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px 22px;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:13px;line-height:1.5;color:#64748b;">Cordialement,<br>Controlis360</p>
                        </td>
                    </tr>
                </table>
                <p style="margin:16px 0 0;font-size:12px;color:#94a3b8;">© {{ date('Y') }} Controlis360</p>
            </td>
        </tr>
    </table>
</body>
</html>
