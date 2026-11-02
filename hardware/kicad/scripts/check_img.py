from PIL import Image

img_path = r"C:\Users\andrl\.gemini\antigravity-cli\brain\efd833bf-6ba1-4a1b-a7ce-aa9aa87aa86c\.user_uploaded\uploaded_media_3_1792445722563.png"
im = Image.open(img_path)
print("Image size:", im.size)
