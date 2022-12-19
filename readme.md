# KIB3 Chatbot Frontend

## Installation 

1. Clone this repository.
2. Move the downloaded code into the following path relative to your Moodle server code top level directory: `./blocks/chatbot`.
   I.e., the folder `./blocks/chatbot/` should contain the version information: `version.php`
3. Open your Moodle administration page in your web browser, this should trigger the plugin installation.
4. Configure the chatbot plugin by entering the IP address of the machine running the chatbot backend (e.g., `127.0.0.1` if you work on your local machine). Also, set the chatbot backend port, if you changed it in the python code (otherwise, default is `44123`):
	<img width="827" alt="Bildschirm­foto 2022-11-14 um 10 15 50" src="https://media.github.tik.uni-stuttgart.de/user/3040/files/a2ff17ee-2a8e-48f0-a8f8-1ba597c07257">

## Adding the Block to Moodle (information taken from https://createdbycocoon.com/knowledge/adding-block-all-courses-moodle)

1. Go to the frontpage of your Moodle site. 
2. Turn editing on
3. Click "+ Add a block"
4. Select `Chatbot`
5. Once the block has been added, click the settings icon, and then `Configure`.
6. Look for setting `Where this block appears`, choose `Show throughout the entire site`.

## Running

1. Navigate to the KIB3 course in your moodle using your webbrowser.
2. Make sure you are a student in the course.
3. If the backend is running and everything is configured correctly, the chatbot window should show in the bottom right corner of the screen:

<img width="488" alt="Bildschirm­foto 2022-11-14 um 10 37 35" src="https://media.github.tik.uni-stuttgart.de/user/3040/files/08ec9c83-3774-4eae-a933-9ab9ed089b7a">

4. If nothing shows up, check both the javascript console and the output of the chatbot backend server.

