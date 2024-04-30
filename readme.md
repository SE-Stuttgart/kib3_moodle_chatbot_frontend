# KIB3 Moodle Assistant Frontend

-----
### German Version: [Here](https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/blob/master/readme_de.md)
----

**NOTE: In the current version, this plugin is only designed to work with the KIB3 Moodle Courses.**

 <img src="https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/assets/48446789/ab9fb75a-9e14-4bcc-9204-d0c50ea231ec" width="500px"/>

## Installation 

0. **Install the [backend](https://github.com/SE-Stuttgart/kib3_moodle_chatbot_backend)**
1. Clone this repository.
2. Move the downloaded code into the following path relative to your Moodle server code top level directory: `./blocks/chatbot`.
   I.e., the folder `./blocks/chatbot/` should contain the version information: `version.php`
3. Open your Moodle administration page in your web browser, this should trigger the plugin installation.

**NOTE: for full assistant functionallity, we recommend also installing [this plugin](https://github.com/SE-Stuttgart/moodle-block_booksearch) which allows searching through PDF content in the course using the assistant.**

## Configuring the Moodle Assistant

1. Go to the administration settings, then navigate to `Plugins`.
2. Click `Chatbot Plugin` in the section `Blocks`. This should open the settings page (see screenshot below).

![settings](https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/assets/48446789/0ed6629e-93bc-4a0d-9bc6-87d6ed972e67)

3. Set `Server name` to the IP address or URL of the server hosting the [Chatbot Backend Server](https://github.com/SE-Stuttgart/kib3_moodle_chatbot_backend).
4. **If you are not running the Moodle Assistant as part of the [docker setup](https://github.com/SE-Stuttgart/kib3_moodle_docker)**, then set `Event Server Name` to the same value as `Server name`. Otherwise, ignore this setting - the default should work for the docker setup.
5. **Only if you changed the default port of the `Chatbot Backend Server` in the python code**, update the `Server port` setting accordingly. Otherwise, leave at default value.
6. Leave `Chat Container` at the default value (if you run into problems with your theme later, you might want to change this setting).
7. **Enable the Moodle Assistant for all Courses that you want it to appear in.**

## Adding the Webservices

1. Navigate to the administration settings, then navigate to `Server`.
2. In the section `Web services`, click `overview`.
3. Ensure that `Enable Web Services` status is `Yes`.
4. Ensure that `Enable protocols` status includes `rest`.
5. Click `Create a specific user` with the following attributes: `Username`: `kib3_webservice`, choose a password, `First name`: `KIB3 Webservice`, `Last name`: `KIB3 Webservice`, choose an email address and create the user.

![webservice user](https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/assets/48446789/8ab816ee-834b-4281-8d29-071b2645f254)

6. Navigate to the administration settings, then navigate to `Users` and in the section `Permissions`, click `Define roles`.
7. Click `Add a new role`.
8. Select archetype: `MANAGER` and continue.
9. Set `Short name` to `kib3webservice` and `Custom full name` to `KIB3 Webservice`. Give a description.
10. Scroll down to the permissions, and allow `moodle/webservice:createtoken` and `webservice/rest:use`
11. Assign this role to the newly created user `kib3_webservice`.
12. Navigate back to the webservices overview page.
13. Click `Select a service`. Underneath `Custom services`, click `Add`.
14. Change `Name` and `Short Name` to `kib3_webservices`. Make sure to tick `Enabled` and `Authorised users only` and save.
![external service settings](https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/assets/48446789/ac899c06-e680-4119-8203-d3c919938c4e)
15. Navigate back to the webservices overview page, then click `Add functions`.
16. Under the section `Custom services`, click the `Functions` link in the row for `kib3_webservices`.
17. Click `Add functions`, and add all functions starting with `block_chatbot_`, `block_slidefinder_`, `mod_icecreamgame_` (only if you installed the [Icecreamgame Plugin](https://github.com/SE-Stuttgart/kib3_moodleplugin_icecreamgame) ) and finally the 2 functions `mod_glossary_get_entries_by_search` and `mod_glossary_get_glossaries_by_courses`.
18. Navigate back to the webservices overview page, then click `Create a token for a user`.
19. Select the `KIB3 Webservice` user and the `kib3_webservices` `Service`, then save changes.
![create webservice user token](https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/assets/48446789/99a2e513-ca7c-49bb-b3e8-8d84b1754d8b)


    
## Adding the Block to Moodle

1. Go to the frontpage of your Moodle site. 
2. Turn editing on
3. Click "+ Add a block"
4. Select `Chatbot`
5. Once the block has been added, click the settings icon, and then `Configure`.
6. Look for setting `Where this block appears`, choose `Any Page`. Don't worry, the assistant **will only show up in courses selected in configuration step 7.**

![block settings](https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/assets/48446789/79d748f8-5293-4bc9-b33a-d8cf56cc1c58)


## Running

1. Navigate to the KIB3 course in your moodle using your webbrowser.
2. Make sure you are a student in the course.
3. If the backend is running and everything is configured correctly, the chatbot window should show in the bottom right corner of the screen:

<img src="https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/assets/48446789/dee29884-8055-4958-89dc-dbeb8603ef13" width="500px"/>

4. If nothing shows up, check both the javascript console and the output of the chatbot backend server.


