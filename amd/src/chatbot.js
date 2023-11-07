import Selectors from './local/selectors';
import DonutChart from './local/charts/donut/donut';
import LineChart from './local/charts/line/line';
import $ from 'jquery';


const registerEventListeners = () => {
    document.addEventListener('click', e => {
        if (e.target.closest(Selectors.actions.maximiseChatWindow)) {
            window.alert("MAXIMISE");
        } else if(e.target.closest(Selectors.actions.minimizeChatWindow)) {
            window.alert("MINIMIZE");
        } else if(e.target.closest(Selectors.actions.sendMessage)) {
            // get value of input field, then send
            const textInputField = $("#block_chatbot-userUtterance");
            const user_input = textInputField.val();
            sendMessage(user_input);
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

const sendMessage = (user_input) => {
    console.log("SENDING");
    // forwad value of input field to socket & send message
    conn.sendMessage(user_input);
    // show user message in messagelist
    addUserMessage(user_input);
    // clear input field
    const textInputField = $("#block_chatbot-userUtterance");
    textInputField.val("");
};


const addUserMessage = (utterance) => {
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

    // scroll to newest message
    messagelist.animate({ scrollTop: messagelist.prop("scrollHeight")}, 500);
};

const renderChart = (utterance) => {
    const messagelist = $('#block_chatbot-messagelist');
    const messageBubble = document.createElement("div");
    messageBubble.className = "block_chatbot-speech-bubble block_chatbot-system";
    const message = document.createElement("div");
    message.className = "block_chatbot-message";
    message.style.color = "anthrazit";

    const args = utterance.split(";");
    const chart_type = args[0].replace("$$", "");
    if(chart_type === "DONUT") {
        const outerValue = args[1];
        const innerValue = args[2];
        const plot = new DonutChart(outerValue, "Kurs", innerValue, "Wiederholte Quizze").render();
        message.append(plot);
        messageBubble.append(message);
        messagelist.append(messageBubble);
    } else if(chart_type === "LINECHART") {
        const legendTitle1 = args[1];
        const values1 = JSON.parse(args[2]);
        const legendTitle2 = args[3];
        const values2 = JSON.parse(args[4]);
        var plot = document.createElement("div");
        plot.className = "block_chatbot-plotly-chart";
        console.log("TITLE", legendTitle1, ",", legendTitle2);
        console.log("DATA1", values1);
        console.log("DATA2", values2);
        message.append(plot);
        messageBubble.append(message);
        messagelist.append(messageBubble);
        new LineChart(plot, legendTitle1, values1, legendTitle2, values2).render(Plotly);
    }
};

const createAnswerCandidateButton = (candidate) => {
    var button = document.createElement("p");
    button.className = "block_chatbot-answer_candidate";
    button.textContent = candidate;
    button.onclick = () => sendMessage(candidate);
    return button;
};

const addSystemMessage = (utterance) => {
    /*
    Adds a new messagebox to the message list
    Args:
        utterance (String): the system utterance
    */
    const messagelist = $('#block_chatbot-messagelist');
    const content = utterance[0];
    const answerCandidates = utterance[1];

    if(content.startsWith("$$")) {
        renderChart(content);
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
    constructor(server_name, server_port, userid, courseid, slidefindertoken, timestamp) {
        this.server_name = server_name;
        this.server_port = server_port;
        this.userid = userid;
        this.courseid = courseid;
        this.slidefindertoken = slidefindertoken;
        this.timestamp = timestamp;
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
                slidefindertoken: this.slidefindertoken,
                timestamp: this.timestamp
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
                } else if(message.party === "control") {
                    if(message.content === "UI_OPEN") {
                        setWindowState(true);
                    }
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
var Plotly;

export const init = (server_name, server_port, server_url, userid, username, courseid, slidefindertoken, timestamp, plotly) => {
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
    console.log("TIMESTAMP", timestamp);

    Plotly = plotly;
    registerEventListeners();
    conn = new ChatbotConnection(server_name, server_port, userid, courseid, slidefindertoken, timestamp);
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
