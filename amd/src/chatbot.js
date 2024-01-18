import Selectors from './local/selectors';
import DonutChart from './local/charts/donut/donut';
import LineChart from './local/charts/line/line';
import {fetchUserSetttings, saveUserSetttings, assignUserSettings, readUserSettings} from './local/settings';
import $ from 'jquery';


const registerEventListeners = () => {
    document.addEventListener('click', e => {
        if(e.target.closest(Selectors.actions.sendMessage)) {
            // get value of input field, then send
            const textInputField = $("#block_chatbot-userUtterance");
            const user_input = textInputField.val();
            sendMessage(user_input);
        } else if(e.target.closest(Selectors.actions.answerCandidate)) {
            const msg = e.target.textContent;
            sendMessage(msg);
        } else if(e.target.closest(Selectors.actions.toggleWindowState)) {
            setWindowState(!(localStorage.getItem("chatbot.maximized") === "true"));
        } else if(e.target.closest(Selectors.actions.toggleWindowSize)) {
            // toggle current size
            const new_size = localStorage.getItem("chatbot.size") === "UI_SIZE_DEFAULT"? "UI_SIZE_LARGE" : "UI_SIZE_DEFAULT";
            resizeWindow(new_size);
        } else if(e.target.closest(Selectors.actions.help)) {
            sendMessage("Hilfe");
        } else if(e.target.closest(Selectors.actions.settings)) {
            // minimize chatbot
            setWindowState(false);
            // open settings modal
            fetchUserSetttings(conn.userid, conn.slidefindertoken, conn.wwwroot).then(settings => {
                // console.log("Settings", settings);
                // apply user settings to dialog modal
                assignUserSettings(settings);
            });
        } else if(e.target.closest(Selectors.actions.saveSettings)) {
            e.preventDefault();
            // convert form output to correct format for sending to DB
            const settings = readUserSettings();
            saveUserSetttings(conn.userid, conn.slidefindertoken, conn.wwwroot, settings).then(result => {
                // console.log("RESULT", result);
            });
        }
    });
    document.addEventListener('keydown', e => {
        if (e.target.closest(Selectors.actions.textInput)) {
            if(e.key === "Enter") {
                // get value of input field, then send
                const textInputField = $("#block_chatbot-userUtterance");
                const user_input = textInputField.val();
                sendMessage(user_input);
            }
        }
    });
};

const sendMessage = (user_input) => {
    // console.log("SENDING", user_input);
    // forwad value of input field to socket & send message
    conn.sendMessage(user_input);
    // show user message in messagelist
    addUserMessage(user_input);
    // clear input field
    const textInputField = $("#block_chatbot-userUtterance");
    textInputField.val("");
};

const extend_chat_history = (party, message) => {
    // extend chat history in local storage, truncate after 10 items
    const storage_history = localStorage.getItem("chatbot.history");
    var chat_history = storage_history===null? [] : JSON.parse(storage_history);

    chat_history.push({party: party, message: message});
    if(chat_history.length > 10) {
        chat_history = chat_history.slice(1);
        $('#block_chatbot-messagelist:first-child').remove();
    }
    localStorage.setItem("chatbot.history", JSON.stringify(chat_history));
};

const restore_chat_history = () => {
    const storage_history = localStorage.getItem("chatbot.history");
    var chat_history = storage_history===null? [] : JSON.parse(storage_history);

    chat_history.forEach(item => {
        if(item.party === 'user') {
            addUserMessage(item.message, false);
        }
        else if(item.party === 'system'){
            addSystemMessage(item.message, false);
        }
    });
};

const addUserMessage = (utterance, shouldScroll = true) => {
    /*
    Adds a new messagebox to the message list
    Args:
        utterance (String): the user utterance
    */
   // remove answer candidates
    $(".block_chatbot-answer_candidate_list").remove();

    // add user message
    const messagelist = $('#block_chatbot-messagelist');
    messagelist.append(`
        <div class="block_chatbot-speech-bubble block_chatbot-user">
            <div class="block_chatbot-message" style="color: anthrazit">${utterance}</div>
        </div>
    `);

    extend_chat_history('user', utterance);

    // scroll to newest message
    if(shouldScroll){
        messagelist.animate({ scrollTop: messagelist.prop("scrollHeight")}, 500);
    } else {
        messagelist.scrollTop(messagelist.prop("scrollHeight"));
    }
};

const renderComponent = (utterance) => {
    const messagelist = $('#block_chatbot-messagelist');
    const messageBubble = document.createElement("div");
    messageBubble.className = "block_chatbot-speech-bubble block_chatbot-system";
    const message = document.createElement("div");
    message.className = "block_chatbot-message";
    message.style.color = "anthrazit";

    const args = utterance.split(";");
    const component_type = args[0].replace("$$", "");
    if(component_type === "DONUT") {
        const outerTitle = args[1];
        const outerValue = args[2];
        const innerTitle = args.length > 3? args[3] : null;
        const innerValue = args.length > 4? args[4] : null;
        const plot = new DonutChart(outerValue, outerTitle, innerValue, innerTitle).render();
        message.append(plot);
        messageBubble.append(message);
        messagelist.append(messageBubble);
    } else if(component_type === "LINECHART") {
        const legendTitle1 = args[1];
        const values1 = JSON.parse(args[2]);
        const legendTitle2 = args[3];
        const values2 = JSON.parse(args[4]);
        var plot = document.createElement("div");
        plot.className = "block_chatbot-plotly-chart";
        // console.log("TITLE", legendTitle1, ",", legendTitle2);
        // console.log("DATA1", values1);
        // console.log("DATA2", values2);
        message.append(plot);
        messageBubble.append(message);
        messagelist.append(messageBubble);
        new LineChart(plot, legendTitle1, values1, legendTitle2, values2).render(Plotly);
    } else if(component_type === "QUIZ") {
        const quiz_args = JSON.parse(args[1]);
        // console.log("QUIZ ARGS", quiz_args);
        messageBubble.style.width = `80%`;
        message.style.width = `100%`;
        var iframe = document.createElement("iframe");
        iframe.src = `${quiz_args.host}/h5p/embed.php?url=${quiz_args.host}/pluginfile.php/${quiz_args.context}` +
                     `/mod_h5pactivity/${quiz_args.filearea}/${quiz_args.itemid}/${quiz_args.filename}` +
                     `&preventredirect=1&component=mod_h5pactivity`;
        iframe.className = "h5p-player border-0 block_chatbot-quiz";
        message.append(iframe);
        messageBubble.append(message);
        messagelist.append(messageBubble);
    }
};


const createAnswerCandidateButton = (candidate) => {
    var button = document.createElement("p");
    button.className = "block_chatbot-answer_candidate";
    button.textContent = candidate;
    button.setAttribute("data-action", "block_chatbot/answerCandidate");
    return button;
};

const addSystemMessage = (utterance, shouldScroll = false) => {
    /*
    Adds a new messagebox to the message list
    Args:
        utterance (String): the system utterance
    */
    const messagelist = $('#block_chatbot-messagelist');
    const content = utterance[0];
    const answerCandidates = utterance[1];

    extend_chat_history('system', utterance);
    const scrollTop = messagelist.prop("scrollHeight");

    if(content.startsWith("$$")) {
        renderComponent(content);
    } else {
        var systemBubble = document.createElement("div");
        systemBubble.className = "block_chatbot-speech-bubble block_chatbot-system";
        var systemMessage = document.createElement("div");
        systemMessage.innerHTML = content;
        systemMessage.className = "block_chatbot-message";
        systemMessage.style.color = "anthrazit";
        systemBubble.append(systemMessage);
        if(answerCandidates.length > 0) {
            var answer_candidate_el = document.createElement("div");
            answer_candidate_el.className = "block_chatbot-answer_candidate_list";
            answerCandidates.forEach(cand => answer_candidate_el.append(createAnswerCandidateButton(cand)));
            systemBubble.append(answer_candidate_el);
        }
        messagelist.append(systemBubble);
    }

    // scroll to newest message
    if(shouldScroll) {
        // messagelist.animate({ scrollTop: messagelist.prop("scrollHeight")}, 500);
    } else {
        messagelist.scrollTop(scrollTop);
    }
};

const setWindowState = (maximized) => {
    // console.log("Window state", maximized);

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

const resizeWindow = (size) => {
    // console.log("Resize to", size);

    if(size == "UI_SIZE_DEFAULT") {
        $(".block_chatbot-chatwindowInner").removeClass('block_chatbot-big');
        $(".block_chatbot-chatwindowInner").addClass('block_chatbot-default');
    } else if(size == "UI_SIZE_LARGE") {
        $(".block_chatbot-chatwindowInner").removeClass('block_chatbot-default');
        $(".block_chatbot-chatwindowInner").addClass('block_chatbot-big');
    }

    localStorage.setItem("chatbot.size", size);
};

class ChatbotConnection {
    constructor(server_name, server_port, wwwroot, userid, courseid, slidefindertoken, wsuserid, timestamp) {
        this.server_name = server_name;
        this.server_port = server_port;
        this.wwwroot = wwwroot;
        this.userid = userid;
        this.courseid = courseid;
        this.slidefindertoken = slidefindertoken;
        this.wsuserid = wsuserid;
        this.timestamp = timestamp;
        this.conn = null;
    }

    openConnection = () => {
        // console.log(`Connecting to: ws://${this.server_name}:${this.server_port}/ws?token=${this.userid}`);
        this.conn = new WebSocket(`ws://${this.server_name}:${this.server_port}/ws?token=${this.userid}`);

        this.conn.onopen = () => {
            // Update Status to Online
            console.log('connected', this.userid);

            const start_dialog_msg = {
                access_token: this.userid,
                domain: 0,
                topic: 'start_dialog',
                courseid: this.courseid,
                slidefindertoken: this.slidefindertoken,
                wsuserid: this.wsuserid,
                timestamp: this.timestamp
            };

            // console.log("START MSG", start_dialog_msg);
            this.conn.send(JSON.stringify(start_dialog_msg));
        };
        this.conn.onmessage = (msg) => {
            // Parse received data
            const data = JSON.parse(msg.data);
            // console.log("Received data", data);
            // render each message
            data.forEach(message => {
                if(message.party === "system") {
                    addSystemMessage(message.content);
                } else if(message.party === "control") {
                    if(message.content === "UI_OPEN") {
                        setWindowState(true);
                    } else if(message.content.startsWith("UI_SIZE")) {
                        resizeWindow(message.content);
                    }
                }
                else {
                    addUserMessage(message.content);
                }
            });
        };
        this.conn.onclose = () => {
            this.conn.close();
            setTimeout(this.openConnection, 2500);
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
        // console.log("Sending message", msg);
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
var Plotly;

export const init = (server_name, server_port, wwwroot, userid, username, courseid, slidefindertoken,
                     wsuserid, timestamp, plotly) => {
    if(isInsideIFrame()) {
        console.log("IFrame detected - Chatbot won't be loaded");
        return;
    }
    // console.log("SERVER", server_name);
    // console.log("PORT", server_port);
    // console.log("WWWROOT", wwwroot);
    // console.log("USER", userid, username);
    // console.log("COURSE", courseid);
    // console.log("SLIDEFINDER TOKEN", slidefindertoken);
    // console.log("WSUERID", wsuserid);
    // console.log("TIMESTAMP", timestamp);

    Plotly = plotly;
    registerEventListeners();
    conn = new ChatbotConnection(server_name, server_port, wwwroot, userid, courseid, slidefindertoken, wsuserid, timestamp);
    conn.openConnection();

    // Move container into document root
    const chatwindow = $("#block_chatbot-chatwindow");
    chatwindow.detach();
    $(document.body).append(chatwindow);

    // Restore chat history
    restore_chat_history();

    // Set or restore minimized state
    if (localStorage.getItem("chatbot.maximized") === null) {
        localStorage.setItem("chatbot.maximized", "false");
    }
    setWindowState(localStorage.getItem("chatbot.maximized") === "true");
    // Set or restore chatbot size
    if (localStorage.getItem("chatbot.size") === null) {
        localStorage.setItem("chatbot.size", "UI_SIZE_DEFAULT");
    }
    resizeWindow(localStorage.getItem("chatbot.size"));

    // Minimize chatbot when clicking outside
    document.addEventListener('click', function(event) {
        var chatbot = document.getElementById('block_chatbot-chatwindow');
        // Check if the clicked element is outside the "chatbot" div
        if ((event.target !== chatbot && !chatbot.contains(event.target)) && !event.target.className.includes('block_chatbot')) {
            // Check if the clicked element is not inside the "chatbot" div
            setWindowState(false);
        }
    });

    return conn;
};
