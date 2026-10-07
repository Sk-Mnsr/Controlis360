<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $bodyText }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background:#0f172a;padding:22px 28px 18px;">
                            <p style="margin:0;font-size:12px;letter-spacing:0.16em;text-transform:uppercase;color:#f59e0b;font-weight:700;">EnchèreSN</p>
                            <p style="margin:6px 0 0;font-size:20px;line-height:1.3;color:#f8fafc;font-weight:700;">Votre offre est gagnante</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:4px;background:#f59e0b;font-size:0;line-height:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:28px;font-size:15px;line-height:1.6;color:#334155;">
                            {!! nl2br(e($bodyText)) !!}
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:14px 28px;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;color:#94a3b8;text-align:center;">EnchèreSN · Message au gagnant</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
