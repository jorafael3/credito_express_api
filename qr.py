import tkinter as tk
from tkinter import messagebox, colorchooser
import qrcode
from PIL import Image, ImageTk
import os

def generate_qr():
    first_name = entry_first_name.get()
    last_name = entry_last_name.get()
    phone = entry_phone.get()
    email = entry_email.get()
    company = entry_company.get()
    job_title = entry_job_title.get()
    address = entry_address.get()
    website = entry_website.get()

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

    qr = qrcode.QRCode(
        version=1,
        error_correction=qrcode.constants.ERROR_CORRECT_L,
        box_size=10,
        border=4,
    )
    qr.add_data(vcard_data)
    qr.make(fit=True)

    color = colorchooser.askcolor(title="Choose QR Code Color")[1]
    if not color:
        color = "#000000"  # Default to black if no color is selected

    img = qr.make_image(fill_color=color, back_color="white").convert("RGBA")

    if transparent_var.get():
        # Create an alpha mask
        datas = img.getdata()
        new_data = []
        for item in datas:
            if item[0] == 255 and item[1] == 255 and item[2] == 255:  # finding white color
                new_data.append((255, 255, 255, 0))  # setting white color to transparent
            else:
                new_data.append(item)
        img.putdata(new_data)

    # Define el nombre del archivo combinando first_name y phone
    output_folder = "qr_codes"
    os.makedirs(output_folder, exist_ok=True)

    # Define el nombre del archivo combinando first_name y phone
    filename = os.path.join(output_folder, f"{first_name}_{phone}.png")
    img.save(filename)

    qr_img = Image.open(filename)
    qr_img = qr_img.resize((200, 200), Image.LANCZOS)
    qr_img = ImageTk.PhotoImage(qr_img)
    label_qr.config(image=qr_img)
    label_qr.image = qr_img
    messagebox.showinfo("QR Code Generated", f"QR Code has been generated and saved as '{filename}'")

app = tk.Tk()
app.title("QR Code Generator")
app.geometry("600x400")

# Frame para los campos de entrada y botón
input_frame = tk.Frame(app)
input_frame.pack(side=tk.LEFT, padx=10, pady=10)

# Etiquetas y campos de entrada
tk.Label(input_frame, text="First Name:").pack(anchor=tk.W)
entry_first_name = tk.Entry(input_frame)
entry_first_name.pack(anchor=tk.W)

tk.Label(input_frame, text="Last Name:").pack(anchor=tk.W)
entry_last_name = tk.Entry(input_frame)
entry_last_name.pack(anchor=tk.W)

tk.Label(input_frame, text="Phone:").pack(anchor=tk.W)
entry_phone = tk.Entry(input_frame)
entry_phone.pack(anchor=tk.W)

tk.Label(input_frame, text="Email:").pack(anchor=tk.W)
entry_email = tk.Entry(input_frame)
entry_email.pack(anchor=tk.W)

tk.Label(input_frame, text="Company:").pack(anchor=tk.W)
entry_company = tk.Entry(input_frame)
entry_company.pack(anchor=tk.W)

tk.Label(input_frame, text="Job Title:").pack(anchor=tk.W)
entry_job_title = tk.Entry(input_frame)
entry_job_title.pack(anchor=tk.W)

tk.Label(input_frame, text="Address:").pack(anchor=tk.W)
entry_address = tk.Entry(input_frame)
entry_address.pack(anchor=tk.W)

tk.Label(input_frame, text="Website:").pack(anchor=tk.W)
entry_website = tk.Entry(input_frame)
entry_website.pack(anchor=tk.W)

# Checkbutton para opción de fondo transparente
transparent_var = tk.BooleanVar()
transparent_check = tk.Checkbutton(input_frame, text="Transparent Background", variable=transparent_var)
transparent_check.pack(anchor=tk.W)

# Botón para generar el código QR
generate_button = tk.Button(input_frame, text="Generate QR Code", command=generate_qr)
generate_button.pack(anchor=tk.W, pady=10)

# Frame para la imagen QR
qr_frame = tk.Frame(app)
qr_frame.pack(side=tk.RIGHT, padx=10, pady=10)

# Etiqueta para mostrar el QR generado
label_qr = tk.Label(qr_frame)
label_qr.pack()

app.mainloop()
