<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Votre code personnel</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background:#0f172a;padding:22px 28px 18px;">
                            <p style="margin:0;font-size:12px;letter-spacing:0.16em;text-transform:uppercase;color:#f59e0b;font-weight:700;">EnchèreSN</p>
                            <p style="margin:6px 0 0;font-size:20px;line-height:1.3;color:#f8fafc;font-weight:700;">Votre code d’accès est personnel</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:4px;background:#f59e0b;font-size:0;line-height:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:28px 28px 8px;">
                            <p style="margin:0 0 12px;font-size:16px;line-height:1.5;">Bonjour <strong>{{ $recipient->name }}</strong>,</p>
                            <p style="margin:0;font-size:15px;line-height:1.6;color:#334155;">
                                Un bien vient d’être déposé. Ce message vous est adressé uniquement à vous : le code ci-dessous ne fonctionne que pour votre compte.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <p style="margin:0 0 4px;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#64748b;font-weight:700;">Bien</p>
                                        <p style="margin:0 0 12px;font-size:16px;font-weight:700;color:#0f172a;">{{ $lotTitle }}</p>
                                        <p style="margin:0 0 4px;font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#64748b;font-weight:700;">Référence</p>
                                        <p style="margin:0;font-size:14px;color:#0f172a;">{{ $lotReference }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 28px 0;" align="center">
                            <p style="margin:0 0 8px;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#b45309;font-weight:700;">Code à saisir</p>
                            <p style="margin:0;display:inline-block;background:#0f172a;color:#f8fafc;font-size:36px;letter-spacing:0.35em;font-weight:700;padding:14px 18px 14px 28px;border-radius:12px;">{{ $code }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 28px 0;">
                            <p style="margin:0;font-size:13px;line-height:1.55;color:#92400e;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 14px;">
                                Ne transférez pas cet e-mail. Un autre membre du comité a reçu un code différent.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 28px 8px;" align="center">
                            <a href="{{ $appUrl }}/vente-encheres/comite" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:12px 22px;border-radius:10px;">Ouvrir l’espace Comité</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 28px;">
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;text-align:center;">
                                Connectez-vous, ouvrez ce bien, puis entrez ces 3 chiffres pour voir les offres des clients.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:14px 28px;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;color:#94a3b8;text-align:center;">EnchèreSN · Enchères sécurisées</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
