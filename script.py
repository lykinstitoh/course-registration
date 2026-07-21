
import cairosvg, os, textwrap

OUT = "./out"
os.makedirs(OUT, exist_ok=True)

NAVY="#1F3864"; BLUE="#2E75B6"; LBLUE="#D6E4F7"
TEAL="#1A7A6E"; LTEAL="#C8EDE9"
AMB="#C07000";  LAMB="#FFF0CC"
GRN="#276221";  LGRN="#D6EDD4"
RED="#8B1A1A";  LRED="#FAD7D7"
GRAY="#555555"; LGRAY="#F4F6F9"
WHITE="#FFFFFF"; BLK="#1A1A2E"

ARROW_DEF = ('<defs><marker id="ar" viewBox="0 0 10 10" refX="8" refY="5" '
             'markerWidth="6" markerHeight="6" orient="auto">'
             '<path d="M1 1L9 5L1 9" fill="none" stroke="#555" '
             'stroke-width="1.5" stroke-linecap="round"/></marker></defs>')

def save(name, svg, w=900, h=600):
    path = f"{OUT}/{name}.png"
    cairosvg.svg2png(bytestring=svg.encode('utf-8'), write_to=path,
                     output_width=w*2, output_height=h*2)
    print(f"  {name}.png  OK")

def wrap(svg_body, vw, vh, title=""):
    t = (f'<text x="{vw//2}" y="28" text-anchor="middle" font-size="18" '
         f'font-weight="bold" fill="{NAVY}">{title}</text>') if title else ""
    return (f'<svg viewBox="0 0 {vw} {vh}" xmlns="http://www.w3.org/2000/svg" '
            f'font-family="Arial"><rect width="{vw}" height="{vh}" fill="{LGRAY}" rx="12"/>'
            f'{ARROW_DEF}{t}{svg_body}</svg>')

def rect(x,y,w,h,fill,stroke,r=7,sw=1.8):
    return f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{r}" fill="{fill}" stroke="{stroke}" stroke-width="{sw}"/>'

def text(x,y,s,fill=BLK,fs=11,fw="normal",anchor="middle"):
    return f'<text x="{x}" y="{y}" text-anchor="{anchor}" font-size="{fs}" font-weight="{fw}" fill="{fill}">{s}</text>'

def line(x1,y1,x2,y2,stroke=GRAY,sw=1.5,dash="",arrow=True):
    me = ' marker-end="url(#ar)"' if arrow else ""
    da = f' stroke-dasharray="{dash}"' if dash else ""
    return f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="{stroke}" stroke-width="{sw}"{da}{me}/>'

def box(x,y,w,h,fill,stroke,label,fs=11):
    lines = label.split("|")
    cy = y+h/2-(len(lines)-1)*8
    out = rect(x,y,w,h,fill,stroke)
    for i,l in enumerate(lines):
        out += text(x+w/2, cy+i*17, l, BLK, fs)
    return out

def diamond(x,y,w,h,fill,stroke,label):
    cx,cy = x+w/2, y+h/2
    pts = f"{cx},{y} {x+w},{cy} {cx},{y+h} {x},{cy}"
    out = f'<polygon points="{pts}" fill="{fill}" stroke="{stroke}" stroke-width="1.8"/>'
    lines = label.split("|")
    base = cy-(len(lines)-1)*7
    for i,l in enumerate(lines):
        out += text(cx, base+i*14, l, BLK, 10)
    return out

def table_box(x,y,title,rows,w=210,hdr=NAVY,fill=LBLUE,stroke=BLUE):
    RH=20; HH=28
    total = HH + len(rows)*RH + 8
    out = rect(x,y,w,total,WHITE,stroke,5,2)
    out += rect(x,y,w,HH,hdr,hdr,5)
    out += rect(x,y+HH-4,w,4,hdr,hdr,0)
    out += text(x+w/2, y+19, title, WHITE, 12, "bold")
    out += f'<line x1="{x}" y1="{y+HH}" x2="{x+w}" y2="{y+HH}" stroke="{stroke}" stroke-width="1.5"/>'
    for i,(col,typ,pk) in enumerate(rows):
        bg = LAMB if pk else WHITE
        out += rect(x+1,y+HH+i*RH,w-2,RH,bg,bg,0)
        clr = "#8B0000" if pk=="PK" else "#005588" if pk=="FK" else BLK
        fw2 = "bold" if pk else "normal"
        prefix = "PK " if pk=="PK" else "FK " if pk=="FK" else ""
        out += text(x+8, y+HH+i*RH+14, prefix+col, clr, 10, fw2, "start")
        out += text(x+w-6, y+HH+i*RH+14, typ, GRAY, 9, "normal", "end")
    out += rect(x,y+HH+len(rows)*RH,w,8,stroke,stroke,5)
    return out, total

def uml_box(x,y,name,attrs,methods,w=220,fill=LBLUE,stroke=BLUE):
    RH=18; HH=30
    ah = len(attrs)*RH+10; mh = len(methods)*RH+10
    total = HH+ah+mh+4
    out = rect(x,y,w,total,WHITE,stroke,5,1.8)
    out += rect(x,y,w,HH,fill,stroke,5)
    out += rect(x,y+HH-2,w,4,fill,fill,0)
    out += text(x+w/2, y+20, name, NAVY, 12, "bold")
    out += f'<line x1="{x}" y1="{y+HH}" x2="{x+w}" y2="{y+HH}" stroke="{stroke}" stroke-width="1"/>'
    for i,a in enumerate(attrs):
        out += text(x+6, y+HH+10+i*RH, a, BLK, 10, "normal", "start")
    out += f'<line x1="{x}" y1="{y+HH+ah}" x2="{x+w}" y2="{y+HH+ah}" stroke="{stroke}" stroke-width="1"/>'
    for i,m in enumerate(methods):
        out += text(x+6, y+HH+ah+10+i*RH, m, BLK, 10, "normal", "start")
    return out, total

# ═══════════════════════════════
# 1. HIGH-LEVEL ARCHITECTURE
# ═══════════════════════════════
def gen_arch():
    out = ""
    cols = [
        ("PRESENTATION TIER", BLUE, 160),
        ("APPLICATION TIER",  TEAL, 450),
        ("DATA TIER",         AMB,  740),
    ]
    for lbl,clr,cx in cols:
        out += text(cx, 66, lbl, clr, 13, "bold")
    out += f'<line x1="305" y1="55" x2="305" y2="540" stroke="#CCC" stroke-width="1" stroke-dasharray="6,4" marker-end=""/>'
    out += f'<line x1="595" y1="55" x2="595" y2="540" stroke="#CCC" stroke-width="1" stroke-dasharray="6,4" marker-end=""/>'

    pres = ["Student Portal","Admin Panel","Registrar Dashboard","Finance Dashboard","Reports &amp; Analytics"]
    app  = ["Auth &amp; RBAC Module","Application Engine","Course Reg Engine","Finance &amp; Payment","Notification Engine"]
    data = ["MySQL Database","Redis Cache","Document Store (S3)","M-Pesa Daraja API","SMS / Email API"]

    fills = [LBLUE,LTEAL,LAMB]
    strks = [BLUE, TEAL, AMB]
    xs    = [60,   330,  630]
    lists = [pres, app, data]
    for col_i,(items,fx,fill,stroke) in enumerate(zip(lists,xs,fills,strks)):
        for row_i,item in enumerate(items):
            y0 = 82+row_i*74
            out += box(fx,y0,220,52,fill,stroke,item,12)
            if col_i < 2:
                out += line(fx+220,y0+26, fx+330,y0+26, GRAY,1.8)
    return wrap(out, 900, 540, "High-Level System Architecture — Three-Tier Model")
save("01_architecture", gen_arch(), 900, 540)

# ═══════════════════════════════
# 2. USE CASE DIAGRAM
# ═══════════════════════════════
def gen_uc():
    out = ""
    # System boundary
    out += rect(200,48,560,552,WHITE,BLUE,14,2)
    out += f'<rect x="200" y="48" width="560" height="2" rx="0" fill="{BLUE}"/>'
    out += text(480,72,"Online Course Registration System",BLUE,13,"bold")

    # Use cases
    student_ucs = [
        (108,"Apply for Programme"),
        (164,"Select Course Units"),
        (220,"Pay Fees (M-Pesa)"),
        (276,"View Timetable &amp; Results"),
        (332,"Upload Documents"),
        (388,"View Fee Statement"),
    ]
    admin_ucs = [
        (452,"Review Applications"),
        (508,"Configure Intakes &amp; Programmes"),
        (564,"Generate Enrolment Reports"),
    ]
    for cy,lbl in student_ucs+admin_ucs:
        out += f'<ellipse cx="480" cy="{cy}" rx="130" ry="22" fill="{LBLUE}" stroke="{BLUE}" stroke-width="1.5"/>'
        out += text(480, cy+5, lbl, NAVY, 11)

    def actor(x,y,lbl,clr):
        s = f'<circle cx="{x}" cy="{y}" r="18" fill="none" stroke="{clr}" stroke-width="2"/>'
        s += f'<line x1="{x}" y1="{y+18}" x2="{x}" y2="{y+68}" stroke="{clr}" stroke-width="2"/>'
        s += f'<line x1="{x-28}" y1="{y+38}" x2="{x+28}" y2="{y+38}" stroke="{clr}" stroke-width="2"/>'
        s += f'<line x1="{x}" y1="{y+68}" x2="{x-22}" y2="{y+96}" stroke="{clr}" stroke-width="2"/>'
        s += f'<line x1="{x}" y1="{y+68}" x2="{x+22}" y2="{y+96}" stroke="{clr}" stroke-width="2"/>'
        s += text(x, y+112, lbl, clr, 12, "bold")
        return s

    out += actor(90, 140, "Student", NAVY)
    out += actor(820,140, "Admin / Registrar", TEAL)
    out += actor(820,430, "Finance Officer", AMB)

    for cy,_ in student_ucs:
        out += line(108,190,350,cy,NAVY,1.2,"3,2")
    for cy,_ in admin_ucs:
        out += line(802,190,610,cy,TEAL,1.2,"3,2")
    for cy in [220,388,564]:
        out += line(802,458,610,cy,AMB,1.2,"3,2")

    return wrap(out, 920, 630, "Use Case Diagram — OCRS")
save("02_use_case", gen_uc(), 920, 630)

# ═══════════════════════════════
# 3. REGISTRATION FLOWCHART
# ═══════════════════════════════
def gen_flow():
    out = ""
    W = 700

    def start_end(x,y,lbl,fill=NAVY):
        out2 = f'<ellipse cx="{x}" cy="{y}" rx="80" ry="24" fill="{fill}" stroke="{fill}"/>'
        out2 += text(x,y+5,lbl,WHITE,13,"bold")
        return out2

    def arr(x1,y1,x2,y2,lbl="",lside=False):
        s = line(x1,y1,x2,y2)
        if lbl:
            lx = (x1+x2)//2 + (8 if not lside else -8)
            s += text(lx,(y1+y2)//2,lbl,GRAY,10,"normal","start" if not lside else "end")
        return s

    # Flow items: (y, type, label, fill, stroke)
    steps = [
        (60,  "start", "START", NAVY, NAVY),
        (120, "box",   "Student Creates Account|&amp; Verifies Email", LBLUE, BLUE),
        (200, "dia",   "Email|Verified?", LAMB, AMB),
        (280, "box",   "Fill Application Form|Upload KCSE Certificate", LGRN, GRN),
        (360, "dia",   "KCSE Grade|Meets Minimum?", LRED, RED),
        (440, "box",   "Admin Reviews Application", LBLUE, BLUE),
        (520, "dia",   "Application|Approved?", LAMB, AMB),
        (600, "box",   "Offer Letter Sent|via Email &amp; SMS", LTEAL, TEAL),
        (680, "box",   "Student Initiates|M-Pesa Payment", LAMB, AMB),
        (760, "dia",   "Payment|Confirmed?", LRED, RED),
        (840, "box",   "Fee Clearance Unlocked", LGRN, GRN),
        (920, "box",   "Student Selects Units|Prerequisite Check", LBLUE, BLUE),
        (1000,"end",   "REGISTERED", NAVY, NAVY),
    ]
    CX = 350
    for (y,typ,lbl,fill,stroke) in steps:
        if typ=="start" or typ=="end":
            out += start_end(CX,y,lbl,fill)
        elif typ=="box":
            out += box(CX-160,y-28,320,56,fill,stroke,lbl)
        elif typ=="dia":
            out += diamond(CX-110,y-34,220,68,fill,stroke,lbl)

    # Down arrows
    pairs = [(60,84),(120,172),(200,252),(280,332),(360,412),(440,492),
             (520,572),(600,652),(680,732),(760,812),(840,892),(920,976)]
    for y1,y2 in pairs:
        out += arr(CX,y1+(24 if y1==60 else 28),CX,y2,"Yes" if y1 in [200,360,520,760] else "")

    # "No" side exits
    out += f'<line x1="{CX-110}" y1="200" x2="130" y2="200" stroke="{RED}" stroke-width="1.5" marker-end="url(#ar)"/>'
    out += f'<line x1="130" y1="200" x2="130" y2="120" stroke="{RED}" stroke-width="1.5"/>'
    out += f'<line x1="130" y1="120" x2="{CX-160}" y2="120" stroke="{RED}" stroke-width="1.5" marker-end="url(#ar)"/>'
    out += text(100,160,"No",RED,10,"normal","end")

    out += f'<line x1="{CX+110}" y1="360" x2="600" y2="360" stroke="{RED}" stroke-width="1.5"/>'
    out += f'<line x1="600" y1="360" x2="600" y2="1000" stroke="{RED}" stroke-width="1.5"/>'
    out += f'<line x1="600" y1="1000" x2="{CX+160}" y2="1000" stroke="{RED}" stroke-width="1.5" marker-end="url(#ar)"/>'
    out += text(608,660,"No — Rejected",RED,9,"normal","start")

    out += f'<line x1="{CX-110}" y1="520" x2="80" y2="520" stroke="{AMB}" stroke-width="1.5"/>'
    out += f'<line x1="80" y1="520" x2="80" y2="1000" stroke="{AMB}" stroke-width="1.5"/>'
    out += f'<line x1="80" y1="1000" x2="{CX-160}" y2="1000" stroke="{AMB}" stroke-width="1.5" marker-end="url(#ar)"/>'
    out += text(58,750,"No",AMB,10,"normal","end")

    out += f'<line x1="{CX+110}" y1="760" x2="550" y2="760" stroke="{RED}" stroke-width="1.5"/>'
    out += f'<line x1="550" y1="760" x2="550" y2="682" stroke="{RED}" stroke-width="1.5" marker-end="url(#ar)"/>'
    out += text(558,720,"Retry",RED,9,"normal","start")

    return wrap(out, W, 1040, "Student Course Registration Flowchart")
save("03_registration_flow", gen_flow(), 700, 1040)

# ═══════════════════════════════
# 4. M-PESA FLOW
# ═══════════════════════════════
def gen_mpesa():
    out = ""
    lanes = [
        ("Student Portal", LBLUE, BLUE, 100),
        ("OCRS Backend",   LTEAL, TEAL, 250),
        ("Safaricom Daraja API", LAMB, AMB, 440),
        ("Student Phone",  LRED,  RED,  640),
    ]
    for lbl,fill,stroke,cx in lanes:
        out += rect(cx-85,46,170,530,fill,stroke,8,1)
        out += text(cx,65,lbl,stroke,11,"bold")
        out += line(cx,74,cx,570,stroke,1.2,"6,4",False)

    msgs = [
        (100,250, 94,  "1. Click Pay Fees"),
        (250,440, 128, "2. Generate STK Push"),
        (440,640, 162, "3. STK Prompt on Phone"),
        (640,640, 196, "4. Student Enters PIN"),
        (640,440, 230, "5. PIN Confirmed"),
        (440,440, 264, "6. Process Payment"),
        (440,250, 298, "7. Callback → Backend"),
        (250,250, 332, "8. Verify Signature"),
        (250,250, 366, "9. Update Fee Ledger"),
        (250,100, 400, "10. Show Receipt"),
        (250,100, 434, "11. Unlock Registration"),
        (250,640, 468, "12. SMS Confirmation"),
    ]
    for x1,x2,y,lbl in msgs:
        da = "5,3" if x2 < x1 else ""
        out += line(x1+(5 if x2>x1 else -5),y,x2-(5 if x2>x1 else -5),y,GRAY,1.4,da)
        mid = (x1+x2)//2
        out += text(mid,y-5,lbl,BLK,9)

    # Step circles
    for i,(x1,x2,y,_) in enumerate(msgs,1):
        mid = (x1+x2)//2
        out += f'<circle cx="{mid}" cy="{y+14}" r="9" fill="{NAVY}"/>'
        out += text(mid,y+19,str(i),WHITE,8,"bold")

    return wrap(out, 760, 590, "M-Pesa STK Push Payment Flow (Safaricom Daraja API)")
save("04_mpesa_flow", gen_mpesa(), 760, 590)

# ═══════════════════════════════
# 5. UML CLASS DIAGRAM
# ═══════════════════════════════
def gen_class():
    out = ""
    classes = [
      (20, 30, "Student",
       ["- studentId : BINARY","- fullName : String","- email : String","- phone : String","- kcseGrade : String"],
       ["+ apply()","+ registerUnits()","+ payFee()","+ viewResults()"],
       LBLUE, BLUE, 220),
      (280,30, "Application",
       ["- appId : BINARY","- studentId : FK","- programmeId : FK","- status : Enum","- submittedAt : DateTime"],
       ["+ submit()","+ approve()","+ reject()","+ sendOffer()"],
       LTEAL, TEAL, 220),
      (540,30, "Programme",
       ["- programmeId : BINARY","- name : String","- code : String","- minKcseGrade : String","- cueApproved : Bool"],
       ["+ getUnits()","+ checkEligibility()"],
       LAMB, AMB, 220),
      (20, 320, "Enrolment",
       ["- enrolmentId : BINARY","- studentId : FK","- programmeId : FK","- status : Enum"],
       ["+ enrol()","+ defer()","+ withdraw()"],
       LGRN, GRN, 220),
      (280,320, "CourseUnit",
       ["- unitId : BINARY","- code : String","- name : String","- creditHours : Int","- capacity : Int","- semester : Int"],
       ["+ getPrerequisites()","+ checkCapacity()"],
       LBLUE, BLUE, 220),
      (540,320, "Registration",
       ["- regId : BINARY","- studentId : FK","- unitId : FK","- semester : String","- status : Enum"],
       ["+ register()","+ drop()","+ validate()"],
       LRED, RED, 220),
      (20, 590, "Invoice",
       ["- invoiceId : BINARY","- studentId : FK","- amount : Decimal","- status : Enum"],
       ["+ generate()","+ sendReminder()"],
       LAMB, AMB, 220),
      (280,590, "Payment",
       ["- paymentId : BINARY","- invoiceId : FK","- amount : Decimal","- mpesaRef : String"],
       ["+ process()","+ verify()"],
       LTEAL, TEAL, 220),
      (540,590, "Document",
       ["- docId : BINARY","- studentId : FK","- type : Enum","- status : Enum"],
       ["+ upload()","+ verify()","+ reject()"],
       LGRN, GRN, 220),
    ]
    for x,y,name,attrs,methods,fill,stroke,w in classes:
        part,_ = uml_box(x,y,name,attrs,methods,w,fill,stroke)
        out += part

    # Relationships
    rels = [
        (240,140,280,140,"1  applies"),
        (500,140,540,140,"registers for  *"),
        (240,410,280,410,"enrolled in  1"),
        (500,410,540,410,"registers  *"),
        (240,660,280,660,"receives  1..*"),
        (500,660,540,660,"uploads  *"),
        (160,290,160,320,"1"),
        (400,290,400,320,"1..*"),
    ]
    for x1,y1,x2,y2,lbl in rels:
        out += line(x1,y1,x2,y2,GRAY,1.3,"5,3")
        out += text((x1+x2)//2,(y1+y2)//2-5,lbl,GRAY,9)

    return wrap(out, 780, 810, "UML Class Diagram — Core System Entities")
save("05_class_diagram", gen_class(), 780, 810)

# ═══════════════════════════════
# 6. ERD
# ═══════════════════════════════
def gen_erd():
    out = ""
    tables = [
      (20, 30, "students",
       [("student_id","BINARY","PK"),("full_name","VARCHAR",""),("email","VARCHAR",""),
        ("phone","VARCHAR",""),("kcse_grade","VARCHAR",""),("password_hash","TEXT",""),("created_at","TIMESTAMP","")]),
      (265,30, "programmes",
       [("programme_id","BINARY","PK"),("name","VARCHAR",""),("code","VARCHAR",""),
        ("level","ENUM",""),("min_kcse_grade","VARCHAR",""),("cue_approved","BOOLEAN",""),("capacity","INT","")]),
      (510,30, "intakes",
       [("intake_id","BINARY","PK"),("programme_id","BINARY","FK"),("label","VARCHAR",""),
        ("open_date","DATE",""),("close_date","DATE",""),("academic_year","VARCHAR","")]),
      (20, 310, "applications",
       [("app_id","BINARY","PK"),("student_id","BINARY","FK"),("programme_id","BINARY","FK"),
        ("intake_id","BINARY","FK"),("status","ENUM",""),("submitted_at","TIMESTAMP","")]),
      (265,310, "enrolments",
       [("enrolment_id","BINARY","PK"),("student_id","BINARY","FK"),("programme_id","BINARY","FK"),
        ("intake_id","BINARY","FK"),("academic_year","VARCHAR",""),("status","ENUM","")]),
      (510,310, "course_units",
       [("unit_id","BINARY","PK"),("programme_id","BINARY","FK"),("code","VARCHAR",""),
        ("name","VARCHAR",""),("credit_hours","INT",""),("semester","INT",""),("capacity","INT","")]),
      (20, 580, "unit_prerequisites",
       [("id","BINARY","PK"),("unit_id","BINARY","FK"),("required_unit_id","BINARY","FK")]),
      (265,580, "unit_registrations",
       [("reg_id","BINARY","PK"),("student_id","BINARY","FK"),("unit_id","BINARY","FK"),
        ("semester","VARCHAR",""),("status","ENUM",""),("registered_at","TIMESTAMP","")]),
      (510,580, "invoices",
       [("invoice_id","BINARY","PK"),("student_id","BINARY","FK"),("semester","VARCHAR",""),
        ("total_amount","DECIMAL",""),("due_date","DATE",""),("status","ENUM","")]),
      (20, 790, "payments",
       [("payment_id","BINARY","PK"),("invoice_id","BINARY","FK"),("amount","DECIMAL",""),
        ("mpesa_ref","VARCHAR",""),("method","ENUM",""),("paid_at","TIMESTAMP","")]),
      (265,790, "documents",
       [("doc_id","BINARY","PK"),("student_id","BINARY","FK"),("doc_type","ENUM",""),
        ("file_url","TEXT",""),("status","ENUM",""),("uploaded_at","TIMESTAMP","")]),
      (510,790, "notifications",
       [("notif_id","BINARY","PK"),("student_id","BINARY","FK"),("channel","ENUM",""),
        ("message","TEXT",""),("sent_at","TIMESTAMP",""),("status","ENUM","")]),
    ]
    
    # Draw each table box
    for x,y,name,cols in tables:
        part,_ = table_box(x,y,name,cols)
        out += part

    # Relationship lines with proper labeling
    # Format: (x1,y1,x2,y2, relationship_type, label)
    # relationship_type: "one-to-many", "many-to-one", "many-to-many"
    
    relationships = [
        # students → applications (one-to-many)
        (160, 190, 160, 310, "one-to-many", "has many"),
        
        # students → enrolments (one-to-many)
        (160, 190, 160, 580, "one-to-many", "enrols in"),
        
        # students → unit_registrations (one-to-many)
        (388, 190, 388, 580, "one-to-many", "registers for"),
        
        # students → invoices (one-to-many)
        (630, 190, 630, 580, "one-to-many", "has"),
        
        # students → payments (one-to-many)
        (388, 580, 388, 790, "one-to-many", "makes"),
        
        # students → documents (one-to-many)
        (630, 580, 630, 790, "one-to-many", "submits"),
        
        # students → notifications (one-to-many)
        (160, 580, 160, 790, "one-to-many", "receives"),
        
        # programmes → intakes (one-to-many)
        (510, 98, 510, 310, "one-to-many", "has"),
        
        # programmes → applications (one-to-many)
        (388, 310, 388, 190, "many-to-one", "for"),
        
        # programmes → enrolments (one-to-many)
        (265, 310, 265, 580, "one-to-many", "enrols in"),
        
        # programmes → course_units (one-to-many)
        (630, 310, 630, 580, "one-to-many", "offers"),
        
        # intakes → applications (one-to-many)
        (20, 310, 20, 580, "one-to-many", "for"),
        
        # course_units → unit_prerequisites (one-to-many)
        (510, 580, 510, 310, "many-to-one", "has"),
        
        # unit_prerequisites → course_units (self-reference)
        (630, 580, 630, 310, "many-to-one", "requires"),
        
        # invoices → payments (one-to-many)
        (20, 580, 20, 790, "one-to-many", "has"),
        
        # intakes → enrolments (one-to-many)
        (388, 580, 388, 310, "many-to-one", "during"),
    ]
    
    # Draw relationship lines with proper notation
    for x1, y1, x2, y2, rel_type, label in relationships:
        # Draw the line
        if rel_type == "one-to-many":
            # One side: simple line, Many side: crow's foot
            out += line_with_crows_foot(x1, y1, x2, y2, GRAY, 1.2)
        elif rel_type == "many-to-one":
            out += line_with_crows_foot_reverse(x1, y1, x2, y2, GRAY, 1.2)
        else:  # many-to-many
            out += line_with_crows_foot_both(x1, y1, x2, y2, GRAY, 1.2)
        
        # Add relationship label at midpoint
        mid_x = (x1 + x2) // 2
        mid_y = (y1 + y2) // 2
        out += text(mid_x - 20, mid_y - 5, label, GRAY, 8, "normal", "start")

    # Legend with updated relationship notation
    out += rect(20, 1000, 760, 55, WHITE, "#CCC", 6, 1)
    
    # PK and FK legend
    out += rect(32, 1010, 18, 12, LAMB, LAMB, 2)
    out += text(56, 1021, "PK = Primary Key", BLK, 10, "normal", "start")
    out += text(220, 1021, "FK = Foreign Key (references another table)", BLK, 10, "normal", "start")
    
    # One-to-many relationship legend
    out += line(470, 1016, 520, 1016, GRAY, 1.2, "5,3", False)
    out += text(470, 1030, "─◀  One-to-Many", BLK, 9, "normal", "start")
    
    # Many-to-one relationship legend
    out += line(600, 1016, 650, 1016, GRAY, 1.2, "5,3", False)
    out += text(600, 1030, "▶─  Many-to-One", BLK, 9, "normal", "start")
    
    return wrap(out, 760, 1060, "")


# Helper function for one-to-many line with crow's foot
def line_with_crows_foot(x1, y1, x2, y2, color, width):
    """Draw relationship line with crow's foot on the many side (right/bottom)"""
    out = ""
    # Determine orientation
    if abs(x2 - x1) > abs(y2 - y1):
        # Horizontal line
        if x2 > x1:
            # Left to right: one on left, many on right
            out += line(x1, y1, x2 - 15, y2, color, width, "")
            # Crow's foot (many) at right side
            out += line(x2 - 15, y2 - 6, x2 - 15, y2 + 6, color, width, "")
            out += line(x2 - 15, y2 - 6, x2, y2, color, width, "")
            out += line(x2 - 15, y2 + 6, x2, y2, color, width, "")
            # One line (single) at left side
            out += line(x1 + 10, y1 - 5, x1 + 10, y1 + 5, color, width, "")
            out += line(x1, y1, x1 + 10, y1, color, width, "")
        else:
            # Right to left: one on right, many on left
            out += line(x1, y1, x2 + 15, y2, color, width, "")
            out += line(x2 + 15, y2 - 6, x2 + 15, y2 + 6, color, width, "")
            out += line(x2 + 15, y2 - 6, x2, y2, color, width, "")
            out += line(x2 + 15, y2 + 6, x2, y2, color, width, "")
            out += line(x1 - 10, y1 - 5, x1 - 10, y1 + 5, color, width, "")
            out += line(x1, y1, x1 - 10, y1, color, width, "")
    else:
        # Vertical line
        if y2 > y1:
            # Top to bottom: one on top, many on bottom
            out += line(x1, y1, x2, y2 - 15, color, width, "")
            out += line(x2 - 6, y2 - 15, x2 + 6, y2 - 15, color, width, "")
            out += line(x2 - 6, y2 - 15, x2, y2, color, width, "")
            out += line(x2 + 6, y2 - 15, x2, y2, color, width, "")
            out += line(x1 - 5, y1 + 10, x1 + 5, y1 + 10, color, width, "")
            out += line(x1, y1, x1, y1 + 10, color, width, "")
        else:
            # Bottom to top: one on bottom, many on top
            out += line(x1, y1, x2, y2 + 15, color, width, "")
            out += line(x2 - 6, y2 + 15, x2 + 6, y2 + 15, color, width, "")
            out += line(x2 - 6, y2 + 15, x2, y2, color, width, "")
            out += line(x2 + 6, y2 + 15, x2, y2, color, width, "")
            out += line(x1 - 5, y1 - 10, x1 + 5, y1 - 10, color, width, "")
            out += line(x1, y1, x1, y1 - 10, color, width, "")
    return out


def line_with_crows_foot_reverse(x1, y1, x2, y2, color, width):
    """Draw relationship line with crow's foot on the one side (many-to-one)"""
    # Simply swap the direction
    return line_with_crows_foot(x2, y2, x1, y1, color, width)


def line_with_crows_foot_both(x1, y1, x2, y2, color, width):
    """Draw relationship line with crow's foot on both sides (many-to-many)"""
    out = ""
    # Draw first crow's foot at x1, y1
    out += line_with_crows_foot(x1, y1, (x1+x2)//2, (y1+y2)//2, color, width)
    # Draw second crow's foot at x2, y2
    out += line_with_crows_foot_reverse((x1+x2)//2, (y1+y2)//2, x2, y2, color, width)
    return out
save("06_erd", gen_erd(), 760, 1048)

# ═══════════════════════════════
# 7. UI WIREFRAMES — Student
# ═══════════════════════════════
def gen_ui_student():
    out = ""
    def scrn(x,y,title,body,w=340,h=250):
        s = rect(x,y,w,h,WHITE,BLUE,8,1.8)
        s += rect(x,y,w,30,NAVY,NAVY,8)
        s += rect(x,y+24,w,6,NAVY,NAVY,0)
        s += text(x+w//2,y+19,title,WHITE,11,"bold")
        for di,dc in enumerate([RED,AMB,GRN]):
            s += f'<circle cx="{x+10+di*14}" cy="{y+15}" r="4" fill="{dc}"/>'
        s += body
        return s

    def fld(x,y,w,lbl):
        return (text(x,y,lbl,GRAY,9,"normal","start")
                +rect(x,y+2,w,16,LGRAY,"#CCC",3,1))

    def btn(x,y,w,lbl,fill=BLUE):
        return (rect(x,y,w,20,fill,fill,4)
                +text(x+w//2,y+14,lbl,WHITE,10,"bold"))

    # Screen 1: Login
    b1 = (text(180,64,"OCRS Portal",NAVY,16,"bold")
          +text(180,78,"Student Registration Portal",GRAY,9)
          +fld(50,88,240,"Email Address")
          +fld(50,114,240,"Password")
          +btn(50,140,240,"Sign In")
          +text(170,172,"Continue with Google",BLUE,9)
          +text(170,190,"New student? Create Account",GRAY,9))

    # Screen 2: Dashboard
    b2 = (rect(20,40,290,50,LBLUE,BLUE,6)
          +text(30,57,"Welcome, Jane Wanjiru",NAVY,10,"bold","start")
          +text(30,72,"BSc IT  |  Year 2  |  Jan 2026 Intake",GRAY,9,"normal","start")
          +rect(32,78,70,14,LGRN,GRN,8)+text(67,88,"Enrolled",GRN,8)
          +text(30,112,"Quick Actions",NAVY,9,"bold","start")
          +btn(30,118,85,"Register",BLUE)+btn(128,118,85,"Pay Fee",AMB)+btn(224,118,86,"Results",TEAL)
          +text(30,152,"Fee Balance",NAVY,9,"bold","start")
          +rect(30,158,284,26,LAMB,AMB,5)
          +text(38,174,"Outstanding: KES 42,000  |  Due: 30 Jul 2026",AMB,9,"normal","start")
          +text(30,200,"Registered Units — Sem 1 2026",NAVY,9,"bold","start")
          +text(30,213,"IT301 — Software Engineering",BLK,8,"normal","start")
          +text(30,225,"IT302 — Database Systems",BLK,8,"normal","start")
          +text(30,237,"IT303 — Computer Networks II",BLK,8,"normal","start"))

    # Screen 3: Course Registration
    units = [
        ("IT301","Software Engineering","32/40",GRN,"ADD"),
        ("IT302","Database Systems","40/40",RED,"FULL"),
        ("IT303","Networks II","18/35",GRN,"ADD"),
        ("IT304","Human-Computer Interface","12/30",GRN,"ADD"),
    ]
    b3 = text(20,46,"Course Registration — Semester 1, 2026/27",NAVY,10,"bold","start")
    b3 += rect(20,52,300,16,LGRAY,"#CCC",4)+text(28,63,"Search units...",GRAY,9,"normal","start")
    for i,(code,name,cap,clr,action) in enumerate(units):
        y0 = 76+i*42
        b3 += rect(20,y0,300,37,LGRAY if i%2==0 else LBLUE,"#DDD",5)
        b3 += text(28,y0+14,code,NAVY,10,"bold","start")
        b3 += text(28,y0+26,name+"  |  3 Cr  |  "+cap,GRAY,8,"normal","start")
        b3 += rect(276,y0+6,36,18,clr,clr,9)
        b3 += text(294,y0+19,action,WHITE,8,"bold")
    b3 += btn(20,246,130,"Confirm Registration",GRN)
    b3 += text(210,258,"3 / 6 units selected",GRAY,9)

    # Screen 4: Fee Payment
    b4 = (text(20,46,"Fee Payment — Semester 1, 2026/27",NAVY,10,"bold","start")
          +rect(20,52,300,52,LAMB,AMB,6)
          +text(28,67,"Total Fees:  KES 72,000",BLK,9,"normal","start")
          +text(28,82,"Amount Paid:  KES 30,000",BLK,9,"normal","start")
          +text(28,96,"Balance Due:  KES 42,000",RED,9,"bold","start")
          +text(20,120,"Pay via M-Pesa STK Push",NAVY,9,"bold","start")
          +fld(20,126,300,"M-Pesa Phone Number")
          +fld(20,152,300,"Amount to Pay (KES)")
          +btn(20,178,300,"Send STK Push to My Phone",TEAL)
          +text(170,210,"Or: Bank Paybill 522533  Acc: Student ID",GRAY,8)
          +rect(20,218,300,20,LGRN,GRN,5)
          +text(170,231,"Min 60% required to unlock registration",GRN,8))

    out += text(365,24,"UI Wireframes — Student Portal",NAVY,17,"bold")
    out += scrn(10, 36,"Screen 1: Student Login",b1)
    out += scrn(365,36,"Screen 2: Student Dashboard",b2)
    out += scrn(10, 300,"Screen 3: Course Unit Registration",b3)
    out += scrn(365,300,"Screen 4: Fee Payment (M-Pesa)",b4)

    return f'<svg viewBox="0 0 720 580" xmlns="http://www.w3.org/2000/svg" font-family="Arial"><rect width="720" height="580" fill="#E4ECF7" rx="12"/>{ARROW_DEF}{out}</svg>'
save("07_ui_student", gen_ui_student(), 720, 580)

# ═══════════════════════════════
# 8. UI WIREFRAMES — Admin
# ═══════════════════════════════
def gen_ui_admin():
    out = ""
    def scrn(x,y,title,body,w=340,h=268):
        s = rect(x,y,w,h,WHITE,TEAL,8,1.8)
        s += rect(x,y,w,30,NAVY,NAVY,8)
        s += rect(x,y+24,w,6,NAVY,NAVY,0)
        s += text(x+w//2,y+19,title,WHITE,11,"bold")
        for di,dc in enumerate([RED,AMB,GRN]):
            s += f'<circle cx="{x+10+di*14}" cy="{y+15}" r="4" fill="{dc}"/>'
        s += body
        return s

    def btn(x,y,w,lbl,fill=BLUE):
        return rect(x,y,w,20,fill,fill,4)+text(x+w//2,y+14,lbl,WHITE,10,"bold")

    # Screen 5: Applications Queue
    apps = [
        ("Wanjiru, Jane A.","B+","12 Jun 2026",AMB,"PENDING"),
        ("Kamau, Peter N.","A-","11 Jun 2026",GRN,"APPROVED"),
        ("Otieno, Grace M.","C+","10 Jun 2026",RED,"REJECTED"),
        ("Mwangi, Kevin J.","B","09 Jun 2026",AMB,"PENDING"),
    ]
    b5 = text(20,47,"Applications — Jan 2027 Intake",NAVY,10,"bold","start")
    b5 += text(300,47,"Filter",BLUE,9,"normal","end")
    for i,(name,grade,date,clr,status) in enumerate(apps):
        y0 = 56+i*48
        b5 += rect(20,y0,300,42,LGRAY,"#DDD",5)
        b5 += text(28,y0+15,name,NAVY,10,"bold","start")
        b5 += text(28,y0+29,"BSc IT  |  KCSE: "+grade+"  |  "+date,GRAY,8,"normal","start")
        b5 += rect(238,y0+10,62,16,clr,clr,8)+text(269,y0+21,status,WHITE,8,"bold")
    b5 += btn(20,252,138,"Review Selected",BLUE)+btn(172,252,148,"Bulk Approve",GRN)

    # Screen 6: Reports
    b6 = text(20,47,"Enrolment Overview — 2026/27",NAVY,10,"bold","start")
    b6 += rect(20,54,138,62,LBLUE,BLUE,6)
    b6 += text(89,78,"2,418",NAVY,22,"bold")
    b6 += text(89,96,"Total Enrolled Students",GRAY,8)
    b6 += rect(172,54,148,62,LTEAL,TEAL,6)
    b6 += text(246,78,"KES 18M",TEAL,18,"bold")
    b6 += text(246,96,"Fees Collected",GRAY,8)
    progs = [("BSc. Information Technology",120,BLUE,"892"),
             ("BA Business Administration",96,TEAL,"714"),
             ("BSc. Nursing",80,AMB,"602"),("BEd. Arts",36,GRN,"210")]
    b6 += text(20,132,"Enrolment by Programme",NAVY,9,"bold","start")
    for i,(prog,w2,clr,n) in enumerate(progs):
        y0 = 140+i*22
        b6 += text(20,y0+12,prog,BLK,8,"normal","start")
        b6 += rect(170,y0,w2,14,clr,clr,3)+text(176+w2,y0+11,n,GRAY,8,"normal","start")
    b6 += btn(20,248,140,"Export to Excel",TEAL)+btn(172,248,148,"CUE Report (PDF)",NAVY)

    # Screen 7: Admin Unit Management
    b7 = text(20,47,"Programmes &amp; Unit Catalogue",NAVY,10,"bold","start")
    b7 += text(20,62,"BSc. Information Technology",BLUE,9,"bold","start")
    b7 += text(20,76,"Year 2 — Semester 1",GRAY,8,"normal","start")
    units7 = [("IT301","Software Engineering","3 Cr","Active"),
              ("IT302","Database Systems","3 Cr","Active"),
              ("IT303","Networks II","3 Cr","Active"),
              ("IT304","HCI","2 Cr","Draft"),]
    for i,(code,name,cr,st) in enumerate(units7):
        y0 = 82+i*38
        b7 += rect(20,y0,300,33,LGRAY,"#DDD",5)
        b7 += text(28,y0+14,code+" — "+name,NAVY,9,"bold","start")
        b7 += text(28,y0+26,cr+"  |  Status: "+st,GRAY,8,"normal","start")
        clr2 = GRN if st=="Active" else AMB
        b7 += rect(270,y0+9,42,14,clr2,clr2,7)+text(291,y0+19,st,WHITE,7)
    b7 += btn(20,242,138,"+ Add Unit",BLUE)+btn(172,242,148,"Set Prerequisites",TEAL)

    # Screen 8: Finance Dashboard
    b8 = text(20,47,"Finance Dashboard — Sem 1, 2026/27",NAVY,10,"bold","start")
    for i,(lbl,val,fill,stroke) in enumerate([
        ("Fees Collected","KES 18.2M",LGRN,GRN),
        ("Outstanding","KES 4.1M",LRED,RED),
        ("Defaulters","203 Students",LAMB,AMB),
    ]):
        x0 = 20+i*100
        b8 += rect(x0,55,94,44,fill,stroke,6)
        b8 += text(x0+47,74,val,stroke,9,"bold")
        b8 += text(x0+47,86,lbl,GRAY,7)
    b8 += text(20,114,"Recent Payments",NAVY,9,"bold","start")
    for i,(nm,amt,mt,dt) in enumerate([
        ("Jane W.","KES 36,000","M-Pesa","Today"),
        ("Peter K.","KES 72,000","Bank","Yesterday"),
        ("Grace O.","KES 18,000","M-Pesa","10 Jun"),
    ]):
        y0 = 120+i*36
        b8 += rect(20,y0,300,31,LGRAY,"#DDD",5)
        b8 += text(28,y0+13,nm+"  |  "+amt,NAVY,9,"normal","start")
        b8 += text(28,y0+24,mt+"  |  "+dt,GRAY,8,"normal","start")
        b8 += rect(260,y0+8,54,14,LGRN,GRN,7)+text(287,y0+18,"VERIFIED",GRN,7)
    b8 += btn(20,244,140,"Export Statements",TEAL)+btn(172,244,148,"Defaulters Report",RED)

    out += text(360,24,"UI Wireframes — Admin &amp; Finance Panel",NAVY,17,"bold")
    out += scrn(10, 36,"Screen 5: Applications Queue",b5)
    out += scrn(365,36,"Screen 6: Enrolment Reports",b6)
    out += scrn(10, 326,"Screen 7: Programme &amp; Unit Catalogue",b7)
    out += scrn(365,326,"Screen 8: Finance Dashboard",b8)

    return f'<svg viewBox="0 0 720 628" xmlns="http://www.w3.org/2000/svg" font-family="Arial"><rect width="720" height="628" fill="#E4ECF7" rx="12"/>{ARROW_DEF}{out}</svg>'
save("08_ui_admin", gen_ui_admin(), 720, 628)

# ═══════════════════════════════
# 9. SEQUENCE DIAGRAM
# ═══════════════════════════════
def gen_seq():
    out = ""
    actors = [
        ("Student",       80,  LBLUE, BLUE),
        ("Portal UI",     230, LTEAL, TEAL),
        ("OCRS API",      390, LAMB,  AMB),
        ("PostgreSQL",    550, LGRN,  GRN),
        ("SMS / Email",   710, LRED,  RED),
    ]
    for name,x,fill,stroke in actors:
        out += rect(x-60,42,120,28,fill,stroke,6,1.5)
        out += text(x,59,name,stroke,11,"bold")
        out += line(x,70,x,590,stroke,1.2,"6,4",False)

    msgs = [
        (80,230,  98, "1. Open Registration Page"),
        (230,390, 128,"2. GET /api/units?semester"),
        (390,550, 158,"3. SELECT units WHERE available"),
        (550,390, 188,"4. Return unit list","ret"),
        (390,230, 218,"5. Return unit data","ret"),
        (230,80,  248,"6. Display unit catalogue","ret"),
        (80,230,  278,"7. Select units + Submit"),
        (230,390, 308,"8. POST /api/register"),
        (390,550, 338,"9. CHECK prerequisites"),
        (550,390, 368,"10. Prereqs: OK","ret"),
        (390,550, 398,"11. CHECK fee clearance"),
        (550,390, 428,"12. Clearance: OK","ret"),
        (390,550, 458,"13. INSERT unit_registrations"),
        (550,390, 488,"14. Saved OK","ret"),
        (390,710, 518,"15. Send SMS + Email"),
        (390,230, 548,"16. Return 201 Created","ret"),
        (230,80,  578,"17. Show registration slip","ret"),
    ]
    for m in msgs:
        x1,x2,y,lbl = m[0],m[1],m[2],m[3]
        ret = len(m)>4
        da = "5,3" if ret else ""
        clr = GRAY
        if x2 > x1:
            out += line(x1+5,y,x2-5,y,clr,1.4,da)
        else:
            out += line(x1-5,y,x2+5,y,clr,1.4,da)
        out += text((x1+x2)//2,y-5,lbl,BLK,9)

    # Activation boxes
    for x,y1,y2,fill in [(230,93,585,LTEAL),(390,123,555,LAMB),(550,153,495,LGRN),(710,513,530,LRED)]:
        out += rect(x-4,y1,8,y2-y1,fill,GRAY,2,1)

    return wrap(out,760,610,"Sequence Diagram — Unit Registration Process")
save("09_sequence", gen_seq(), 760, 610)

print("\nAll 9 diagrams generated successfully.")


