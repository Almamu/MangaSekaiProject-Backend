![logo](doc/img/logo.png)

Eyes on the logo from LordBarta on deviantart: https://www.deviantart.com/lordbarta/art/Shock-Eyes-589962803

# 1. What is MangaSekaiProject
Manga Sekai Project aims to provide a selfhosted alternative to online manga readers. Keep track of your reading status, tag and organize your local library and keep it up to your quality standards.

# 2. Requirements
- PHP 8.2 or newer
- Composer
- A web server

# 3. Keep your library organized
Right now Manga Sekai Project expects the following folder structure:
- Manga Name
    - Chapter 1
        - 1.jpg 
        - 2.jpg
        - 3.jpg
        - 4.jpg
        - ...
        
The chapter's folder name doesn't really matter as long as there is a number in it. The same happens with the page files.
Any of the folders can be a zip, so these two structures should also work:
- Manga Name.zip
    - Chapter 1
        - 1.jpg 
        - 2.jpg
        - 3.jpg
        - 4.jpg
        - ...
        
or
- Manga Name
    - Chapter 1.zip
        - 1.jpg 
        - 2.jpg
        - 3.jpg
        - 4.jpg
        - ...
