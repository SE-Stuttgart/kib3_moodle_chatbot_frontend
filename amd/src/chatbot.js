import Selectors from './local/selectors';
import $ from 'jquery';


const registerEventListeners = () => {
    document.addEventListener('click', e => {
        if (e.target.closest(Selectors.actions.maximiseChatWindow)) {
            window.alert("MAXIMISE");
        } else if(e.target.closest(Selectors.actions.minimizeChatWindow)) {
            window.alert("MINIMIZE");
        } else if(e.target.closest(Selectors.actions.sendMessage)) {
            sendMessage();
        } else if(e.target.closest(Selectors.actions.toggleWindowState)) {
            setWindowState(!(localStorage.getItem("chatbot.maximized") === "true"));
        }
    });
    document.addEventListener('keydown', e => {
        if (e.target.closest(Selectors.actions.textInput)) {
            if(e.key === "Enter") {
              sendMessage();
            }
        }
    });
};

const sendMessage = () => {
    const textInputField = $("#block_chatbot-userUtterance");
    // get value of input field
    const user_input = textInputField.val();
    // forwad value of input field to socket & send message
    conn.sendMessage(user_input);
    // show user message in messagelist
    addUserMessage(user_input);
    // clear input field
    textInputField.val("");
};

const addUserMessage = (utterance) => {
    /*
    Adds a new messagebox to the message list
    Args:
        utterance (String): the user utterance
    */
    const messagelist = $('#block_chatbot-messagelist');
    messagelist.append(`
        <div class="block_chatbot-speech-bubble block_chatbot-user">
            <div class="block_chatbot-message" style="color: anthrazit">${utterance}</div>
        </div>
    `);
    // scroll to newest message
    messagelist.animate({ scrollTop: messagelist.prop("scrollHeight")}, 500);
};

const addSystemMessage = (utterance) => {
    /*
    Adds a new messagebox to the message list
    Args:
        utterance (String): the system utterance
    */
    const messagelist = $('#block_chatbot-messagelist');
    messagelist.append(`
        <div class="block_chatbot-speech-bubble block_chatbot-system">
            <div class="block_chatbot-message" style="color: anthrazit">${utterance}</div>
        </div>
    `);
    // scroll to newest message
    messagelist.animate({ scrollTop: messagelist.prop("scrollHeight")}, 500);
};

const setWindowState = (maximized) => {
    console.log("Window state", maximized);

    if(maximized) {
        $("#block_chatbot-messagelist").removeClass('block_chatbot-hidden');
        $(".block_chatbot-inputContainer").removeClass('block_chatbot-hidden');
        $(".block_chatbot-chatwindowInner").removeClass('block_chatbot-hidden');
        $(".block_chatbot-headerMinimized").addClass('block_chatbot-hidden');
    } else {
        $("#block_chatbot-messagelist").addClass('block_chatbot-hidden');
        $(".block_chatbot-inputContainer").addClass('block_chatbot-hidden');
        $(".block_chatbot-chatwindowInner").addClass('block_chatbot-hidden');
        $(".block_chatbot-headerMinimized").removeClass('block_chatbot-hidden');
    }
    // remember state
    localStorage.setItem("chatbot.maximized", maximized? "true" : "false");
};

class ChatbotConnection {
    constructor(server_name, server_port, userid, courseid, slidefindertoken) {
        this.server_name = server_name;
        this.server_port = server_port;
        this.userid = userid;
        this.courseid = courseid;
        this.slidefindertoken = slidefindertoken;
        this.conn = null;
    }

    openConnection = () => {
        console.log(`Connecting to: ws://${this.server_name}:${this.server_port}/ws?token=${this.userid}`);
        this.conn = new WebSocket(`ws://${this.server_name}:${this.server_port}/ws?token=${this.userid}`);

        this.conn.onopen = () => {
            // Update Status to Online
            console.log('connected', this.userid);

            const start_dialog_msg = {
                access_token: this.userid,
                domain: 0,
                topic: 'start_dialog',
                courseid: this.courseid,
                slidefindertoken: this.slidefindertoken
            };

            console.log("START MSG", start_dialog_msg);
            this.conn.send(JSON.stringify(start_dialog_msg));
        };
        this.conn.onmessage = (msg) => {
            // Parse received data
            const data = JSON.parse(msg.data);
            console.log("Received data", data);
            // render each message
            data.forEach(message => {
                if(message.party === "system") {
                    addSystemMessage(message.content);
                }
                else {
                    addUserMessage(message.content);
                }
            });
        };
    };

    sendMessage = (message) => {
        const msg = {
            userid: this.userid,
            domain: 0,
            topic: 'user_utterance',
            courseid: this.courseid,
            msg: message
        };
        console.log("Sending message", msg);
        this.conn.send(JSON.stringify(msg));
    };
}

const isInsideIFrame = () => {
    if (window.location !== window.parent.location)
    {
        // inside iframe
        return true;
    }
    else {
        // The page is not in an iFrame
        return false;
    }
};

var conn;


export const init = (server_name, server_port, server_url, userid, username, courseid, slidefindertoken) => {
    if(isInsideIFrame()) {
        console.log("IFrame detected - Chatbot won't be loaded");
        return;
    }
    console.log("SERVER", server_name);
    console.log("PORT", server_port);
    console.log("URL", server_url);
    console.log("USER", userid, username);
    console.log("COURSE", courseid);
    console.log("SLIDEFINDER TOKEN", slidefindertoken);

    registerEventListeners();
    conn = new ChatbotConnection(server_name, server_port, userid, courseid, slidefindertoken);
    conn.openConnection();

    // Move container into document root
    const chatwindow = $("#block_chatbot-chatwindow");
    chatwindow.detach();
    $(document.body).append(chatwindow);

    // Set or restore minimized state
    if (localStorage.getItem("chatbot.maximized") === null) {
        localStorage.setItem("chatbot.maximized", "false");
    }
    setWindowState(localStorage.getItem("chatbot.maximized") === "true");

    return conn;
};
