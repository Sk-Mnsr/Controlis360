# -*- coding: utf-8 -*-
"""Génère le manuel PDF illustré Gouvernance IT."""

from pathlib import Path

from PIL import Image as PILImage
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_JUSTIFY, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import cm, mm
from reportlab.platypus import (
    Image,
    KeepTogether,
    ListFlowable,
    ListItem,
    PageBreak,
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    Table,
    TableStyle,
)

ROOT = Path(__file__).resolve().parent
IMG = ROOT / "images"
OUT = ROOT / "Manuel_Gouvernance_IT.pdf"

BRAND = colors.HexColor("#a3181f")
SLATE = colors.HexColor("#1e293b")
MUTED = colors.HexColor("#64748b")
LIGHT = colors.HexColor("#f8fafc")
BORDER = colors.HexColor("#cbd5e1")
MUSTARD = colors.HexColor("#d4a017")


def styles():
    base = getSampleStyleSheet()
    return {
        "cover_title": ParagraphStyle(
            "cover_title",
            parent=base["Title"],
            fontName="Helvetica-Bold",
            fontSize=26,
            textColor=BRAND,
            alignment=TA_CENTER,
            spaceAfter=12,
        ),
        "cover_sub": ParagraphStyle(
            "cover_sub",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=13,
            textColor=SLATE,
            alignment=TA_CENTER,
            spaceAfter=8,
        ),
        "h1": ParagraphStyle(
            "h1",
            parent=base["Heading1"],
            fontName="Helvetica-Bold",
            fontSize=16,
            textColor=BRAND,
            spaceBefore=16,
            spaceAfter=8,
            borderPadding=3,
        ),
        "h2": ParagraphStyle(
            "h2",
            parent=base["Heading2"],
            fontName="Helvetica-Bold",
            fontSize=12,
            textColor=SLATE,
            spaceBefore=12,
            spaceAfter=6,
        ),
        "body": ParagraphStyle(
            "body",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=10,
            textColor=SLATE,
            alignment=TA_JUSTIFY,
            leading=14,
            spaceAfter=6,
        ),
        "caption": ParagraphStyle(
            "caption",
            parent=base["Normal"],
            fontName="Helvetica-Oblique",
            fontSize=8,
            textColor=MUTED,
            alignment=TA_CENTER,
            spaceBefore=4,
            spaceAfter=12,
        ),
        "note": ParagraphStyle(
            "note",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=9,
            textColor=SLATE,
            backColor=colors.HexColor("#fef3c7"),
            borderPadding=6,
            leading=12,
            spaceAfter=8,
        ),
        "bullet": ParagraphStyle(
            "bullet",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=10,
            textColor=SLATE,
            leading=13,
        ),
        "th": ParagraphStyle(
            "th",
            parent=base["Normal"],
            fontName="Helvetica-Bold",
            fontSize=8,
            textColor=colors.white,
            leading=11,
        ),
        "td": ParagraphStyle(
            "td",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=8,
            textColor=SLATE,
            leading=11,
        ),
        "footer": ParagraphStyle(
            "footer",
            parent=base["Normal"],
            fontName="Helvetica",
            fontSize=8,
            textColor=MUTED,
            alignment=TA_CENTER,
        ),
    }


def add_page_number(canvas, doc):
    canvas.saveState()
    canvas.setStrokeColor(BORDER)
    canvas.line(2 * cm, 1.4 * cm, A4[0] - 2 * cm, 1.4 * cm)
    canvas.setFont("Helvetica", 8)
    canvas.setFillColor(MUTED)
    canvas.drawString(2 * cm, 0.9 * cm, "Controlis360 — Module Gouvernance IT")
    canvas.drawRightString(A4[0] - 2 * cm, 0.9 * cm, f"Page {doc.page}")
    canvas.restoreState()


def fitted_image(path: Path, max_w, max_h):
    with PILImage.open(path) as im:
        w, h = im.size
    ratio = min(max_w / w, max_h / h)
    return Image(str(path), width=w * ratio, height=h * ratio)


def captioned_image(story, S, path: Path, caption: str, max_w=16.5 * cm, max_h=8.5 * cm):
    if not path.exists():
        story.append(Paragraph(f"[Image manquante : {path.name}]", S["caption"]))
        return
    block = [fitted_image(path, max_w, max_h), Paragraph(caption, S["caption"])]
    story.append(KeepTogether(block))


def table(data, col_widths, S, header=True):
    styled = []
    for i, row in enumerate(data):
        style = S["th"] if header and i == 0 else S["td"]
        styled.append([Paragraph(str(c), style) for c in row])
    t = Table(styled, colWidths=col_widths, repeatRows=1 if header else 0)
    cmds = [
        ("BACKGROUND", (0, 0), (-1, 0), BRAND),
        ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
        ("GRID", (0, 0), (-1, -1), 0.4, BORDER),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("LEFTPADDING", (0, 0), (-1, -1), 4),
        ("RIGHTPADDING", (0, 0), (-1, -1), 4),
        ("TOPPADDING", (0, 0), (-1, -1), 4),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
        ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, LIGHT]),
    ]
    t.setStyle(TableStyle(cmds))
    return t


def build():
    S = styles()
    doc = SimpleDocTemplate(
        str(OUT),
        pagesize=A4,
        leftMargin=2 * cm,
        rightMargin=2 * cm,
        topMargin=1.8 * cm,
        bottomMargin=2 * cm,
        title="Manuel Gouvernance IT — Controlis360",
        author="Controlis360",
    )
    story = []

    # Couverture
    story.append(Spacer(1, 3 * cm))
    story.append(Paragraph("MANUEL D’UTILISATION", S["cover_sub"]))
    story.append(Paragraph("Module Gouvernance IT", S["cover_title"]))
    story.append(Paragraph("GovStrat IT-R — Controlis360", S["cover_sub"]))
    story.append(Spacer(1, 0.6 * cm))
    story.append(
        Paragraph(
            "Guide complet pour Agent IT, Responsable IT,<br/>Responsable Régional et Administrateurs",
            S["cover_sub"],
        )
    )
    story.append(Spacer(1, 1.2 * cm))
    captioned_image(
        story,
        S,
        IMG / "govit-gears.png",
        "Figure 1 — Accueil GovStrat IT-R : sélection des modules (engrenages)",
        max_h=9 * cm,
    )
    story.append(PageBreak())

    # 1 Intro
    story.append(Paragraph("1. Introduction", S["h1"]))
    story.append(
        Paragraph(
            "Le module <b>Gouvernance IT</b> permet de suivre et piloter les activités IT de chaque filiale : "
            "projets, chantiers, incidents, points d’attention, échanges avec le Responsable Régional, "
            "pièces jointes et validation avant envoi.",
            S["body"],
        )
    )
    story.append(
        Paragraph(
            "<b>Chemin d’accès :</b> Portail Controlis360 → module <b>Gouvernance IT</b> → <b>GovStrat IT-R</b>.",
            S["body"],
        )
    )

    # 2 Profils
    story.append(Paragraph("2. Profils et droits d’accès", S["h1"]))
    story.append(
        table(
            [
                ["Profil", "Rôle", "Accès principal"],
                ["Agent IT", "AGIT", "CENTRE SUPPORT, SYSTEMES ET RESEAUX, BASE DE DONNEES"],
                ["Responsable IT", "RESPIT", "Idem Agent + validation des lignes Agent"],
                ["Responsable Régional", "RESPREG", "Task ACTIVITY IT (vue par filiale)"],
                ["Super Admin / Admin", "—", "Tous les modules"],
            ],
            [3.5 * cm, 2.2 * cm, 10.8 * cm],
            S,
        )
    )
    story.append(Spacer(1, 0.3 * cm))
    story.append(
        Paragraph(
            "<b>Règles clés :</b> l’Agent IT ne crée que des <b>Points d’Attention</b> ; "
            "il peut éditer les lignes dont il est <b>Owner</b> (nom exact). "
            "Le Responsable IT gère toutes les sections et valide. "
            "Le Responsable Régional consulte et échange via chat / PJ.",
            S["note"],
        )
    )

    # 3 Engrenages
    story.append(Paragraph("3. Accueil GovStrat IT-R (engrenages)", S["h1"]))
    story.append(
        Paragraph(
            "Les sous-modules s’affichent sous forme d’engrenages. Un clic ouvre le formulaire "
            "<b>directement en dessous</b> sans masquer les modules. Un second clic referme le panneau.",
            S["body"],
        )
    )
    story.append(
        table(
            [
                ["Engrenage", "Visible pour"],
                ["Task ACTIVITY IT", "Responsable Régional, Admin"],
                ["CENTRE SUPPORT", "Agent IT, Responsable IT, Admin"],
                ["SYSTEMES ET RESEAUX", "Agent IT, Responsable IT, Admin"],
                ["BASE DE DONNEES", "Agent IT, Responsable IT, Admin"],
            ],
            [7 * cm, 9.5 * cm],
            S,
        )
    )
    story.append(Spacer(1, 0.4 * cm))
    captioned_image(
        story,
        S,
        IMG / "govit-gears.png",
        "Figure 2 — Modules GovStrat sous forme d’engrenages imbriqués",
    )

    # 4 Tableau
    story.append(Paragraph("4. Ensembles, sections et tableau", S["h1"]))
    story.append(Paragraph("4.1 En-tête filiale / équipe", S["h2"]))
    story.append(
        Paragraph(
            "L’en-tête affiche <b>Filiale</b>, <b>Responsable</b> (IT) et <b>Team</b> (agents IT). "
            "Le <b>+</b> rouge crée un nouvel <b>ensemble</b> (libellé automatique daté).",
            S["body"],
        )
    )
    story.append(Paragraph("4.2 Sections", S["h2"]))
    story.append(
        ListFlowable(
            [
                ListItem(Paragraph("Projets en cours", S["bullet"])),
                ListItem(Paragraph("Chantiers en cours", S["bullet"])),
                ListItem(Paragraph("Chantier Système d'Information Flexcube (SI)", S["bullet"])),
                ListItem(Paragraph("INCIDENTS", S["bullet"])),
                ListItem(Paragraph("Points d'Attention", S["bullet"])),
            ],
            bulletType="1",
            start="1",
        )
    )
    story.append(Spacer(1, 0.2 * cm))
    story.append(
        Paragraph(
            "Le <b>+</b> bleu du bandeau rouge ajoute une ligne. "
            "Agent IT : création limitée aux <b>Points d’Attention</b>.",
            S["body"],
        )
    )
    story.append(Paragraph("4.3 Colonnes", S["h2"]))
    story.append(
        table(
            [
                ["Colonne", "Description"],
                ["N°", "Numéro automatique"],
                ["Titre", "Libellé de l’activité"],
                ["Impact *", "Obligatoire pour Save / Soumettre / Send"],
                ["Owner", "Responsable de la ligne — jaune moutarde si c’est vous"],
                ["Priorité", "P1 / P2 / P3"],
                ["Statut", "OPEN (rouge) ou CLOSE (vert) — CLOSE auto si FINISH DATE"],
                ["Dates", "Livraison, START / FINISH effectives"],
                ["LEAD TIME", "Jours entre START et FINISH (calcul auto)"],
                ["Commentaire", "Note de ligne (≠ chat)"],
                ["Actions", "Chat, PJ, édition, suppression, flux validation / Send"],
            ],
            [3.5 * cm, 13 * cm],
            S,
        )
    )
    story.append(Spacer(1, 0.4 * cm))
    captioned_image(
        story,
        S,
        IMG / "govit-tableau.png",
        "Figure 3 — Tableau d’activités (exemple CENTRE SUPPORT) avec Owner en jaune moutarde",
        max_h=9 * cm,
    )

    # 5 Workflow
    story.append(Paragraph("5. Circuit de validation et envoi", S["h1"]))
    story.append(
        Paragraph(
            "Hors <b>Points d’Attention</b>, toute modification Agent IT doit être validée "
            "par le Responsable IT avant envoi au Responsable Régional.",
            S["body"],
        )
    )
    captioned_image(
        story,
        S,
        IMG / "govit-workflow.png",
        "Figure 4 — Circuit Agent IT → Responsable IT → Responsable Régional",
        max_h=6.5 * cm,
    )
    story.append(
        table(
            [
                ["Étape", "Qui", "Bouton", "Effet"],
                ["1", "Agent IT (Owner)", "Soumettre", "Statut « En attente » (ligne ambre)"],
                ["2", "Responsable IT", "Valider", "Ligne validée"],
                ["3", "Agent ou Resp. IT", "Send", "Envoi Régional → badge Envoyé"],
            ],
            [1.8 * cm, 4 * cm, 3.2 * cm, 7.5 * cm],
            S,
        )
    )
    story.append(Spacer(1, 0.25 * cm))
    story.append(
        Paragraph(
            "<b>Exception Points d’Attention :</b> pas de validation — l’Agent IT peut Send directement.<br/>"
            "<b>Attention :</b> une nouvelle modification Agent annule la validation (re-soumettre).",
            S["note"],
        )
    )

    # 6 Chat & PJ
    story.append(Paragraph("6. Discussion (chat) et pièces jointes", S["h1"]))
    story.append(Paragraph("6.1 Chat sur la ligne", S["h2"]))
    story.append(
        Paragraph(
            "L’icône bulle dans <b>Actions</b> ouvre un fil de messages <b>datés</b> entre "
            "Agent IT, Responsable IT et Responsable Régional. C’est distinct du champ Commentaire.",
            S["body"],
        )
    )
    captioned_image(
        story,
        S,
        IMG / "govit-chat.png",
        "Figure 5 — Modal Discussion ligne (chat daté multi-profils)",
        max_h=8 * cm,
    )
    story.append(Paragraph("6.2 Pièces jointes", S["h2"]))
    story.append(
        Paragraph(
            "L’icône trombone ouvre le modal. Cliquez sur <b>+</b> pour ajouter plusieurs jointures. "
            "Chaque jointure a un champ <b>Nom</b> et un champ <b>Pièce jointe</b> (max 10 Mo). "
            "Puis <b>Joindre</b>. Ensuite : Voir / Télécharger / Supprimer.",
            S["body"],
        )
    )
    captioned_image(
        story,
        S,
        IMG / "govit-pj.png",
        "Figure 6 — Modal Pièces jointes (Nom + fichier, jointure multiple)",
        max_h=8.5 * cm,
    )

    # 7 Task Activity
    story.append(Paragraph("7. Task ACTIVITY IT (Responsable Régional)", S["h1"]))
    story.append(
        Paragraph(
            "Sélectionnez une <b>filiale</b>, consultez les lignes par origine "
            "(CENTRE SUPPORT, SYSTEMES ET RESEAUX, BASE DE DONNEES…), "
            "suivez les motifs (P1, échéance dépassée, Envoyé) et utilisez chat / PJ.",
            S["body"],
        )
    )

    # 8 Parcours
    story.append(Paragraph("8. Parcours types", S["h1"]))
    story.append(Paragraph("8.1 Agent IT — Point d’Attention", S["h2"]))
    story.append(
        Paragraph(
            "Créer un ensemble → section Points d’Attention → + → renseigner Impact / Owner / priorité → Save → Send.",
            S["body"],
        )
    )
    story.append(Paragraph("8.2 Agent IT — Ligne hors Points d’Attention (Owner)", S["h2"]))
    story.append(
        Paragraph(
            "Le Responsable IT crée la ligne et vous met Owner (case jaune) → vous modifiez → Save → "
            "Soumettre → Valider (Resp. IT) → Send.",
            S["body"],
        )
    )
    story.append(Paragraph("8.3 Responsable IT", S["h2"]))
    story.append(
        Paragraph(
            "Repérer les lignes ambre « En attente » → contrôler chat / PJ → Valider → Send.",
            S["body"],
        )
    )
    story.append(Paragraph("8.4 Responsable Régional", S["h2"]))
    story.append(
        Paragraph(
            "Task ACTIVITY IT → choisir filiale → suivre motifs → discuter / joindre des fichiers.",
            S["body"],
        )
    )

    # 9 Couleurs
    story.append(Paragraph("9. Codes couleurs", S["h1"]))
    story.append(
        table(
            [
                ["Élément", "Signification"],
                ["Owner jaune moutarde", "Vous êtes Owner de la ligne"],
                ["OPEN rouge / CLOSE vert", "Statut ouvert / clos"],
                ["Ligne ambre + En attente", "Soumise, en attente de validation"],
                ["Badge Envoyé", "Transmise au Régional (édition verrouillée)"],
                ["Impact *", "Champ obligatoire"],
            ],
            [5.5 * cm, 11 * cm],
            S,
        )
    )

    # 10 FAQ
    story.append(Paragraph("10. FAQ / Dépannage", S["h1"]))
    faqs = [
        (
            "Je ne vois pas CENTRE SUPPORT / SYSTEMES / BASE DE DONNEES",
            "Profil probablement Responsable Régional → utiliser Task ACTIVITY IT.",
        ),
        (
            "Je ne vois pas Task ACTIVITY IT",
            "Réservé au Responsable Régional (et Admin).",
        ),
        (
            "Je ne peux pas créer hors Points d’Attention",
            "Normal pour Agent IT. Demander au Responsable IT de créer la ligne et de vous mettre Owner.",
        ),
        (
            "Save / Send refuse",
            "Vérifier que Impact est renseigné.",
        ),
        (
            "Send refusé pour Agent hors Points d’Attention",
            "La ligne doit d’abord être validée par le Responsable IT.",
        ),
        (
            "Case Owner non jaune",
            "Le nom du compte doit correspondre exactement au Owner de la ligne.",
        ),
    ]
    for q, a in faqs:
        story.append(Paragraph(f"<b>{q}</b><br/>{a}", S["body"]))

    story.append(Spacer(1, 1 * cm))
    story.append(
        Paragraph(
            "— Fin du manuel — Controlis360 / Gouvernance IT / GovStrat IT-R —",
            S["footer"],
        )
    )

    doc.build(story, onFirstPage=add_page_number, onLaterPages=add_page_number)
    print(f"PDF généré : {OUT}")


if __name__ == "__main__":
    build()
