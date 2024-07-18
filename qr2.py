import sys
from PyQt5.QtWidgets import QApplication, QWidget, QVBoxLayout, QHBoxLayout, QLabel, QLineEdit, QPushButton, QColorDialog, QCheckBox
from PyQt5.QtGui import QPixmap
import qrcode
from PIL import Image, ImageQt

def rgb_to_cmyk(r, g, b):
    r, g, b = r / 255.0, g / 255.0, b / 255.0
    k = 1 - max(r, g, b)
    if k == 1:
        c = m = y = 0
    else:
        c = (1 - r - k) / (1 - k)
        m = (1 - g - k) / (1 - k)
        y = (1 - b - k) / (1 - k)
    return c, m, y, k

class QRCodeGenerator(QWidget):
    def __init__(self):
        super().__init__()
        self.initUI()

    def initUI(self):
        self.setWindowTitle('QR Code Generator')
        self.setGeometry(100, 100, 600, 500)

        main_layout = QHBoxLayout()

        # Left side layout for input fields
        left_layout = QVBoxLayout()

        self.label_first_name = QLabel('First Name:')
        self.entry_first_name = QLineEdit()
        left_layout.addWidget(self.label_first_name)
        left_layout.addWidget(self.entry_first_name)

        self.label_last_name = QLabel('Last Name:')
        self.entry_last_name = QLineEdit()
        left_layout.addWidget(self.label_last_name)
        left_layout.addWidget(self.entry_last_name)

        self.label_phone = QLabel('Phone:')
        self.entry_phone = QLineEdit()
        left_layout.addWidget(self.label_phone)
        left_layout.addWidget(self.entry_phone)

        self.label_email = QLabel('Email:')
        self.entry_email = QLineEdit()
        left_layout.addWidget(self.label_email)
        left_layout.addWidget(self.entry_email)

        self.label_company = QLabel('Company:')
        self.entry_company = QLineEdit()
        left_layout.addWidget(self.label_company)
        left_layout.addWidget(self.entry_company)

        self.label_job_title = QLabel('Job Title:')
        self.entry_job_title = QLineEdit()
        left_layout.addWidget(self.label_job_title)
        left_layout.addWidget(self.entry_job_title)

        self.label_address = QLabel('Address:')
        self.entry_address = QLineEdit()
        left_layout.addWidget(self.label_address)
        left_layout.addWidget(self.entry_address)

        self.label_website = QLabel('Website:')
        self.entry_website = QLineEdit()
        left_layout.addWidget(self.label_website)
        left_layout.addWidget(self.entry_website)

        self.button_color = QPushButton('Choose CMYK Color')
        self.button_color.clicked.connect(self.choose_color)
        left_layout.addWidget(self.button_color)

        self.transparent_checkbox = QCheckBox('Transparent Background')
        left_layout.addWidget(self.transparent_checkbox)

        self.button_generate = QPushButton('Generate QR Code')
        self.button_generate.clicked.connect(self.generate_qr)
        left_layout.addWidget(self.button_generate)

        main_layout.addLayout(left_layout)

        # Right side layout for QR code display
        self.label_qr = QLabel()
        main_layout.addWidget(self.label_qr)

        self.setLayout(main_layout)

    def choose_color(self):
        color_dialog = QColorDialog()
        color = color_dialog.getColor()
        if color.isValid():
            self.selected_color = color

    def generate_qr(self):
        first_name = self.entry_first_name.text()
        last_name = self.entry_last_name.text()
        phone = self.entry_phone.text()
        email = self.entry_email.text()
        company = self.entry_company.text()
        job_title = self.entry_job_title.text()
        address = self.entry_address.text()
        website = self.entry_website.text()

        vcard_data = f"""BEGIN:VCARD
VERSION:3.0
N:{last_name};{first_name};;;
FN:{first_name} {last_name}
ORG:{company}
TITLE:{job_title}
TEL;TYPE=WORK,VOICE:{phone}
EMAIL:{email}
ADR;TYPE=WORK:;;{address};;;
URL:{website}
END:VCARD"""

        # Get CMYK color from QColor object
        c = self.selected_color.cyanF()
        m = self.selected_color.magentaF()
        y = self.selected_color.yellowF()
        k = self.selected_color.blackF()

        # Convert CMYK to RGB (for PIL usage)
        r = int(255 * (1 - c) * (1 - k))
        g = int(255 * (1 - m) * (1 - k))
        b = int(255 * (1 - y) * (1 - k))
        fill_color = f'#{r:02x}{g:02x}{b:02x}'

        # Check if transparent background checkbox is checked
        if self.transparent_checkbox.isChecked():
            back_color = None  # Transparent background
        else:
            back_color = "white"

        # Generate QR code using PIL and convert to QPixmap for display
        qr = qrcode.QRCode(
            version=1,
            error_correction=qrcode.constants.ERROR_CORRECT_L,
            box_size=10,
            border=4,
        )
        qr.add_data(vcard_data)
        qr.make(fit=True)

        img = qr.make_image(fill_color=fill_color, back_color=back_color)

        # Convert PIL image to QPixmap for displaying in QLabel
        img_pil = img.convert('RGBA')
        img_qt = ImageQt.ImageQt(img_pil)
        qr_pixmap = QPixmap.fromImage(img_qt)

        # Scale the QPixmap to fit within QLabel
        qr_pixmap = qr_pixmap.scaledToWidth(300)

        self.label_qr.setPixmap(qr_pixmap)

if __name__ == '__main__':
    app = QApplication(sys.argv)
    window = QRCodeGenerator()
    window.show()
    sys.exit(app.exec_())
