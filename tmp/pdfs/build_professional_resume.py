from pathlib import Path
from reportlab.lib import colors
from reportlab.lib.enums import TA_RIGHT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import mm
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, KeepTogether, PageBreak

OUT = Path(__file__).parents[2] / "output" / "pdf" / "Dennis-Dizon-Resume-Professional.pdf"
OUT.parent.mkdir(parents=True, exist_ok=True)

INK = colors.HexColor("#171714")
BLUE = colors.HexColor("#4058EA")
MUTED = colors.HexColor("#666660")
LINE = colors.HexColor("#D7D5CC")

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name="Name", parent=styles["Title"], fontName="Helvetica-Bold", fontSize=25, leading=27, textColor=INK, spaceAfter=3))
styles.add(ParagraphStyle(name="Role", parent=styles["Normal"], fontName="Helvetica", fontSize=10.5, leading=13, textColor=BLUE))
styles.add(ParagraphStyle(name="Contact", parent=styles["Normal"], fontName="Helvetica", fontSize=8.5, leading=12, textColor=MUTED, alignment=TA_RIGHT))
styles.add(ParagraphStyle(name="Section", parent=styles["Heading2"], fontName="Helvetica-Bold", fontSize=9, leading=11, textColor=BLUE, spaceBefore=7, spaceAfter=4, uppercase=True))
styles.add(ParagraphStyle(name="BodySmall", parent=styles["BodyText"], fontName="Helvetica", fontSize=8.7, leading=12.2, textColor=INK, spaceAfter=3))
styles.add(ParagraphStyle(name="Job", parent=styles["Heading3"], fontName="Helvetica-Bold", fontSize=10.2, leading=12, textColor=INK, spaceAfter=1))
styles.add(ParagraphStyle(name="Meta", parent=styles["Normal"], fontName="Helvetica-Oblique", fontSize=8.3, leading=11, textColor=MUTED, spaceAfter=4))
styles.add(ParagraphStyle(name="BulletPro", parent=styles["BodyText"], fontName="Helvetica", fontSize=8.4, leading=11.5, leftIndent=10, firstLineIndent=-7, bulletIndent=0, textColor=INK, spaceAfter=2.5))
styles.add(ParagraphStyle(name="Skill", parent=styles["BodyText"], fontName="Helvetica", fontSize=8.2, leading=11.2, textColor=INK))
styles.add(ParagraphStyle(name="GroupLabel", parent=styles["BodyText"], fontName="Helvetica-Bold", fontSize=8.2, leading=10, textColor=BLUE, spaceBefore=4, spaceAfter=3))
styles.add(ParagraphStyle(name="ProjectTitle", parent=styles["BodyText"], fontName="Helvetica-Bold", fontSize=8.8, leading=11, textColor=INK, spaceAfter=0.5))

def section(title):
    return [Paragraph(title.upper(), styles["Section"]), Table([[""]], colWidths=[178*mm], rowHeights=[0.4], style=TableStyle([("BACKGROUND",(0,0),(-1,-1),LINE), ("LEFTPADDING",(0,0),(-1,-1),0), ("RIGHTPADDING",(0,0),(-1,-1),0)])), Spacer(1, 4)]

def bullet(text):
    return Paragraph("• " + text, styles["BulletPro"])

doc = SimpleDocTemplate(str(OUT), pagesize=A4, rightMargin=16*mm, leftMargin=16*mm, topMargin=14*mm, bottomMargin=13*mm, title="Dennis Dizon - Professional Resume", author="Dennis Antonida Dizon")
story = []

header = Table([
    [Paragraph("DENNIS ANTONIDA DIZON", styles["Name"]), Paragraph("Butuan City, Philippines<br/>+63 909 599 4462<br/><link href='mailto:denzyodfm@gmail.com' color='#4058EA'>denzyodfm@gmail.com</link>", styles["Contact"])],
    [Paragraph("WEB APPLICATION &amp; FILEMAKER DEVELOPER / IT SPECIALIST", styles["Role"]), ""]
], colWidths=[112*mm, 66*mm])
header.setStyle(TableStyle([("VALIGN",(0,0),(-1,-1),"TOP"),("LEFTPADDING",(0,0),(-1,-1),0),("RIGHTPADDING",(0,0),(-1,-1),0),("BOTTOMPADDING",(0,0),(-1,-1),0),("SPAN",(1,0),(1,1))]))
story += [header, Spacer(1, 8)]

story += section("Professional Profile")
story.append(Paragraph("Results-driven developer with 7+ years delivering end-to-end custom applications across the modern web and the FileMaker platform, backed by more than a decade supporting business-critical infrastructure. Builds responsive, secure web applications with Next.js, React, TypeScript, PHP and MySQL, and multi-user business systems that integrate with REST services, SQL databases, and communication APIs. Uses AI-assisted development throughout the delivery cycle to move from requirement to working, reviewed code quickly, with security review and manual verification kept in the loop. Combines application architecture, workflow automation, and infrastructure ownership to improve operational speed, accuracy, and reliability.", styles["BodySmall"]))

story += section("Career Impact")
impact = Table([[Paragraph("<b>40%</b><br/><font size='7' color='#666660'>LESS MANUAL ENTRY</font>", styles["BodySmall"]), Paragraph("<b>30%</b><br/><font size='7' color='#666660'>FASTER PROCESSING</font>", styles["BodySmall"]), Paragraph("<b>25%</b><br/><font size='7' color='#666660'>FASTER LAYOUT LOADS</font>", styles["BodySmall"]), Paragraph("<b>99%</b><br/><font size='7' color='#666660'>INFRASTRUCTURE UPTIME</font>", styles["BodySmall"]) ]], colWidths=[44.5*mm]*4)
impact.setStyle(TableStyle([("BOX",(0,0),(-1,-1),.5,LINE),("INNERGRID",(0,0),(-1,-1),.5,LINE),("VALIGN",(0,0),(-1,-1),"MIDDLE"),("LEFTPADDING",(0,0),(-1,-1),7),("TOPPADDING",(0,0),(-1,-1),6),("BOTTOMPADDING",(0,0),(-1,-1),4)]))
story.append(impact)

story += section("Professional Experience")
story += [Paragraph("Senior FileMaker Developer / IT Specialist", styles["Job"]), Paragraph("Valdemer Resources, Inc. | Butuan City | 2017 - Present", styles["Meta"])]
for item in [
    "Architected a suite of FileMaker applications for inventory, sales, collections, purchasing, lending, and HR workflows supporting 150+ employees; reduced manual entry by 40% and eliminated duplicate spreadsheets.",
    "Built an automated lending, approval, and notification platform using FileMaker Server Data API, REST endpoints, database integration, and SMS APIs; reduced processing delays by 30%.",
    "Developed a centralized web-based client inquiry system for branch-level client and loan verification, remote synchronization, legacy UI continuity, and operational analytics.",
    "Built and shipped web applications for operations, requests, collections, and trading using PHP/MySQL and the React and Next.js stack, applying AI-assisted development to shorten the path from requirement to reviewed, working code.",
    "Implemented role-based security, encryption at rest, and automated off-site backups aligned with ISO 25010 quality parameters.",
    "Migrated legacy databases to FileMaker 19 and optimized relationship graphs and scripts, improving average layout load time by 25%.",
    "Direct corporate IT operations across network design, enterprise firewalls, Windows/Linux servers, virtualization, endpoint security, monitoring, backup, and disaster recovery; maintained 99% uptime.",
]: story.append(bullet(item))

story += section("Technical Expertise")
skills = [
    [Paragraph("<b>Web Application Development</b><br/>Next.js, React, TypeScript, Tailwind CSS, PHP 8, MySQL, Supabase, responsive UI, authentication and session security", styles["Skill"]), Paragraph("<b>AI-Assisted Delivery</b><br/>LLM-assisted scaffolding, refactoring, code review, test generation, and documentation, paired with manual security review", styles["Skill"])],
    [Paragraph("<b>FileMaker Platform</b><br/>Pro/Server 11-20, WebDirect, Data API, ESS/ODBC, relationship graphs, custom functions, modular scripting", styles["Skill"]), Paragraph("<b>Application Delivery</b><br/>Requirements analysis, schema design, UI/UX prototyping, dashboards, reporting, performance tuning, multi-device layouts", styles["Skill"])],
    [Paragraph("<b>Integration &amp; APIs</b><br/>REST/JSON, cURL, JavaScript, MySQL/SQL Server, SMTP, SMS, Telegram, third-party service integration", styles["Skill"]), Paragraph("<b>Infrastructure &amp; Security</b><br/>Windows/Linux Server, Hyper-V, Cisco, Fortinet, MikroTik, Ubiquiti, TP-Link, backup and recovery", styles["Skill"])],
]
tbl = Table(skills, colWidths=[87*mm,87*mm], hAlign="LEFT")
tbl.setStyle(TableStyle([("VALIGN",(0,0),(-1,-1),"TOP"),("BOX",(0,0),(-1,-1),.5,LINE),("INNERGRID",(0,0),(-1,-1),.5,LINE),("LEFTPADDING",(0,0),(-1,-1),7),("RIGHTPADDING",(0,0),(-1,-1),7),("TOPPADDING",(0,0),(-1,-1),6),("BOTTOMPADDING",(0,0),(-1,-1),6)]))
story.append(tbl)

story += section("Selected Projects")
project_groups = [
    ("Web Applications", [
        ("Beach Resort Cottage Booking", "Responsive booking application built with Next.js, React, TypeScript, and Tailwind CSS, with Supabase-ready authentication and database APIs."),
        ("Centralized Client Inquiry System", "Browser-based branch-level client and loan verification with remote synchronization, legacy UI continuity, and operational analytics."),
        ("Fuel Monitoring Dashboard", "Consumption tracking and reporting dashboard for fleet and generator fuel usage."),
        ("SFXC Activity Request", "Digital activity request and multi-level approval workflow replacing paper routing."),
        ("Chapel Collection System", "Collection recording, reconciliation, and reporting with role-based access."),
        ("Zyeon Tire Trading", "Trading, stock, and sales management for a tire distribution business."),
    ]),
    ("Business Systems", [
        ("Centralized Lending &amp; Remedial Platform", "Unified loan origination, credit scoring, payment monitoring, approvals, client verification, and remedial action across branches."),
        ("Human Resources Information System", "Leave filing, medical reimbursements, performance evaluations, and payroll workflows for 150+ employees."),
        ("Real-Time &amp; Scheduled Alert Engine", "Integration with Globe M360 SMS, SMTP, and Telegram APIs for automated client and staff notifications."),
        ("Sales, Inventory, Billing &amp; Collection", "Real-time stock visibility, automated low-stock alerts, invoicing, payment tracking, aging reports, and streamlined follow-up."),
        ("Request, Approval &amp; Liquidation", "Digital requests, multi-level approvals, expense liquidation, audit trails, and attachment management."),
        ("Pawnshop Inventory System", "Multi-branch tracking for pawned items, appraisals, redemption history, valuation, and stock reconciliation."),
    ]),
]
for group_name, group_projects in project_groups:
    story.append(Paragraph(group_name.upper(), styles["GroupLabel"]))
    for title, desc in group_projects:
        story.append(KeepTogether([Paragraph(title, styles["ProjectTitle"]), Paragraph(desc, styles["BodySmall"]), Spacer(1, 1)]))

story += section("Education")
story += [Paragraph("Master of Science in Information Technology", styles["Job"]), Paragraph("Caraga State University | Butuan City", styles["Meta"]), Spacer(1,3), Paragraph("Bachelor of Science in Computer Science, Major in Information System", styles["Job"]), Paragraph("University of Cebu", styles["Meta"])]

story += section("Certifications & Professional Development")
for item in ["Claris FileMaker Certified Developer - in progress", "Advanced FileMaker Scripting & API Workshop - Productive Computing University, 2025", "Cisco CCNA Routing & Switching - coursework completed, 2024"]: story.append(bullet(item))

story += section("Professional Links")
story.append(Paragraph("GitHub: <link href='https://github.com/denzyodfm' color='#4058EA'>github.com/denzyodfm</link>  |  Public repositories are predominantly web applications.", styles["BodySmall"]))

def footer(canvas, doc):
    canvas.saveState(); canvas.setStrokeColor(LINE); canvas.line(16*mm, 10*mm, 194*mm, 10*mm); canvas.setFont("Helvetica", 7); canvas.setFillColor(MUTED); canvas.drawString(16*mm, 6.5*mm, "DENNIS ANTONIDA DIZON"); canvas.drawRightString(194*mm, 6.5*mm, f"PAGE {doc.page}"); canvas.restoreState()

doc.build(story, onFirstPage=footer, onLaterPages=footer)
print(OUT)
