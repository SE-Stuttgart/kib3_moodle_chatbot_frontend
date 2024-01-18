export const fetchUserSetttings = async (userid, wstoken, wwwroot) => {
    // Fetch settings
    const url = wwwroot +
        '/webservice/rest/server.php?wstoken=' +
        wstoken +
        '&moodlewsrestformat=json&wsfunction=block_chatbot_get_usersettings&userid=' +
        userid;
    const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                // 'Content-Type': 'application/x-www-form-urlencoded',
            },
        }
    );
    // Parse settings
    const msgContent = await response.text();
    // handle moodle debug mode, which appends a <script> tag to the response
    const settings = JSON.parse(msgContent.replace(/<script[\s\S]*?<\/script>/gi, ''));
    return settings;
};

export const assignUserSettings = (settings) => {
    document.getElementById("block_chatbot_enabled").checked = settings.enabled;
    document.getElementById("block_chatbot_logging").checked = settings.logging;
    document.getElementById("block_chatbot_openonlogin").checked = settings.openonlogin;
    document.getElementById("block_chatbot_openonquiz").checked = settings.openonquiz;
    document.getElementById("block_chatbot_openonsection").checked = settings.openonsection;
    document.getElementById("block_chatbot_openonbranch").checked = settings.openonbranch;
    document.getElementById("block_chatbot_openonbadge").checked = settings.openonbadge;
    document.getElementById("block_chatbot_numreviewquizzes").value = settings.numreviewquizzes;
    document.getElementById("block_chatbot_numsearchresults").value = settings.numsearchresults;
    new Array("resource", "url", "book").forEach(content_type => {
        document.getElementById('block_chatbot_preferedcontenttype_' + content_type).checked =
             settings.preferedcontenttype == content_type;
    });
};

export const readUserSettings = () => {
    const preferedcontenttype = new Array("resource", "url", "book").map(content_type =>
        document.getElementById('block_chatbot_preferedcontenttype_' + content_type
        )).filter(el => el.checked)[0].id.replace("block_chatbot_preferedcontenttype_", "");
    return {
        enabled: document.getElementById("block_chatbot_enabled").checked,
        logging: document.getElementById("block_chatbot_logging").checked,
        openonlogin: document.getElementById("block_chatbot_openonlogin").checked,
        openonquiz: document.getElementById("block_chatbot_openonquiz").checked,
        openonsection: document.getElementById("block_chatbot_openonsection").checked,
        openonbranch: document.getElementById("block_chatbot_openonbranch").checked,
        openonbadge: document.getElementById("block_chatbot_openonbadge").checked,
        numreviewquizzes: document.getElementById("block_chatbot_numreviewquizzes").value,
        numsearchresults: document.getElementById("block_chatbot_numsearchresults").value,
        preferedcontenttype: preferedcontenttype
    };
};

export const saveUserSetttings = async (userid, wstoken, wwwroot, settings) => {
    // Construct request
    let url = wwwroot +
        '/webservice/rest/server.php?wstoken=' +
        wstoken +
        '&moodlewsrestformat=json&wsfunction=block_chatbot_set_usersettings&userid=' +
        userid;
    Object.keys(settings).forEach(key => {
        const value = typeof settings[key] === "boolean"? Number(settings[key]) : settings[key];
        url += '&' + key + '=' + value;
    });
    // Send request
    const response = await fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            // 'Content-Type': 'application/x-www-form-urlencoded',
        },
    }
    );
    const msgContent = await response.text();
    return true;
};
