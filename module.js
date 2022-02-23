/**
 * Chatbot javascript functions
 *
 * @package    block_chatbot
 */

M.block_chatbot = {
	name : 'chatbot',
	space_between_windows : 15,

	isInsideIFrame : function() {
		if ( window.location !== window.parent.location )
		{
			// inside iframe
			return true;
		} 
		else {
			  
			// The page is not in an iFrame
			return false;
		}
	},

	
	/**
	 * Initiates the connection with the server and creates the chat windows container.
	 * @param object Y - YUI 3 object.
	 * @param string server_name - the server name.
	 * @param string server_port - the server port.
	 * @param string server_url - the server url.
	 * @param string container - the id or class of the chat container.
	 * @param object user - the current user: id, username.
	 * @param object imgs - the images urls needed.
	 */
	init : function(Y, server_name, server_port, server_url, chat_container, user, imgs) {

		console.log("JS INIT");
		console.log('user', user);
		
		if(this.isInsideIFrame()) {
			return; // don't create chatbot window if we're inside an iframe (then it probably would appear twice)
		}

		// Set Global Values
		this.chat_container = chat_container;
		this.user = user;
		this.imgs = imgs;

		// Set or restore minimized state
		if (localStorage.getItem("chatbot.minimized") === null) {
			self.is_minimized = false;
			localStorage.setItem("chatbot.minimized", "false")
		} else {
			this.is_minimized = localStorage.getItem("chatbot.minimized") === 'true';
		}
		
		// Create Chat Windows Container
		var container = Y.one(this.chat_container);
		var chat = Y.Node.create('<div id="chatbot"></div>');
		container.append(chat);

		// Check browser support for WebSockets
		if ("WebSocket" in window) {
			
			//Start Connection
			// this.start_connection(Y, server_name, server_port, server_url, user);
			// 193.196.53.252
			this.start_connection(Y, '193.196.53.252', 44123, '193.196.53.252', user);
		}
		else {
			// The user browser doesn't support WebSockets
			this.error(Y, 'no-support');
		}
	},
	
	
	/**
	 * Start connection with the server
	 * @param object Y - YUI 3 object.
	 * @param string server_name - the server name.
	 * @param string server_port - the server port.
	 * @param string server_url - the server url.
	 * @param object imgs - the images urls needed.
	 */
	start_connection : function(Y, server_name, server_port, server_url, user) {
		var self = this, conn = new WebSocket('ws://'+server_name+':'+server_port+'/ws?token=' + user.id  );

		conn.onopen = function() {

			// Update Status to Online
			console.log('connected', user);
			self.start_session(Y, conn, server_url, null, true);

			start_dialog_msg = {
				access_token: user.id,
				domain: 0,
				topic: 'start_dialog' 
			}
			conn.send(JSON.stringify(start_dialog_msg));
		};

		let that = this;
		conn.onmessage = function(msg) {
			// Parse data received.
	        var data = JSON.parse(msg.data);
			console.log('recv', data);
			
			var session = 1;
			var chat_window_messages = Y.one('#chatbot_session_'+session).one('.messages');

			// display messages in text form
			data.forEach(msg => {
				const params = {
					session: {
						id: session
					},
					user: {
						id: msg.party == "system"? 0 : 1,
						username: msg.party == "system"? 'adviser' : user.username
					},
					message: msg.content,
					format: msg.format
				};
				that.create_message(Y, chat_window_messages, params);
			});
			
			//Scroll to Bottom
			that.messages_scroll_bottom(Y, chat_window_messages);
	    };

		conn.onerror = function() {
			conn.close();
		};

		conn.onclose = function() {
			setTimeout(function() {
				// Try to re-connect every 5 seconds
				self.start_connection(Y, server_name, server_port, server_url, user);
			}, 5000);
			
			Y.all('.chat_window').remove(true);
			self.error(Y, 'connection-lost');
		};
	},
	
	
	/**
	 * Sends data from the client to the server.
	 * @param object Y - YUI 3 object.
	 * @param object conn - the connection with the server.
	 * @param string action - the action to be performed on server side.
	 * @param object params - the data sent by the server.
	 */
	send_data : function(Y, conn, action, params) {
		dialog_msg = {
			userid: params.user.id,
			domain: 0,
			topic: 'user_utterance',
			msg: params.message.text
		}
		console.log('sending', params);
		conn.send(JSON.stringify(dialog_msg));
	},
	
	
	/**
	 * Creates a user HTML to add to the block.
	 * @param object Y - YUI 3 object.
	 * @param object conn - the connection with the server.
	 * @param object user - the user data.
	 * @param int item_class - the element class.
	 * @return Node item.
	 */
	create_user : function(Y, conn, user, item_class) {
		var self = this;
		var item = Y.Node.create('<li class="r'+item_class+'"></li>');
		var item_div = Y.Node.create('<div class="column c1 user"></div>');
		item_div.setAttribute('id', 'chatbot_user_'+user.id);

		var table = Y.Node.create('<table></table>');
		var table_row = Y.Node.create('<tr></tr>');

		//User Picture
		var table_col_picture = Y.Node.create('<td class="picture"></td>');
		var user_picture = Y.Node.create(user.picture);
		table_col_picture.append(user_picture);
		table_row.append(table_col_picture);

		//User Name
		var table_col_name = Y.Node.create('<td class="name"></td>');
		var user_name = Y.Node.create('<p></p>');
		user_name.setContent(user.username);
		table_col_name.append(user_name);
		table_row.append(table_col_name);

		//User Status
		var table_col_status = Y.Node.create('<td class="status"></td>');
		var user_status = Y.Node.create('<span></span>');
		user_status.addClass(user.status);
		table_col_status.append(user_status);
		table_row.append(table_col_status);

		table.append(table_row);
		item_div.append(table);
		item.append(item_div);

		//Item click event to start a session with the user
		item_div.on('click', function(event) {
			var id = this.getAttribute('id').toString().split('_')[2];

			var params = {};
			params.from_id = self.user.id;
			params.to_id = id;
			
			self.send_data(Y, conn, 'start_session', params);
		});

		return item;
	},
	

	/**
	 * Receives all open sessions from the server and adds them to the chat container.
	 * @param object Y - YUI 3 object.
	 * @param object conn - the connection with the server.
	 * @param object params - the data sent by the server.
	 */
	get_sessions : function(Y, conn, server_url, params) {
		if(params.sessions) {
			for(i in params.sessions) {
				this.start_session(Y, conn, server_url, params.sessions[i], false);
			}
		}
	},
	
	
	/**
	 * Starts a session with other user and creates the chat window.
	 * @param object Y - YUI 3 object.
	 * @param object conn - the connection with the server.
	 * @param string server_url - the server url.
	 * @param object params - the data sent by the server.
	 * @param bool focus - to focus the chat window or not.
	 */
	start_session : function(Y, conn, server_url, params, focus) {
		var self = this;
		
		var session_id = 1; // params.session.id;
		var user_to_id = 2; // params.session.user_to
		var user_to_name = 'Adviser';

		var chat = Y.one('#chatbot');
		var chat_window_id = 'chatbot_session_'+session_id;
		var chat_window = Y.one('#'+chat_window_id);

		if(!chat_window) {

			// Create Window
			if(session_id > 0) {
				console.log('creating chat window');
				var all_chat_windows = chat.all('.chat_window');
				var total_chat_windows = all_chat_windows.size();
				var chat_window_width = 0;

				if(total_chat_windows > 0) {
					chat_window_width = parseInt(all_chat_windows.getStyle('width'));
				}
				var right_space = ((total_chat_windows* (chat_window_width + this.space_between_windows)) + this.space_between_windows);

				chat_window = Y.Node.create('<div></div>');
				chat_window.setAttribute('id', chat_window_id);
				chat_window.addClass('chat_window chatbot_userto_'+user_to_id);
				chat_window.setStyle('right', right_space+'px');
				
				if(this.is_minimized) {
					chat_window.addClass("collapsed");
				}

				chat.append(chat_window);

				// Window Header
				var chat_window_header = Y.Node.create('<div></div>');
				chat_window_header.addClass('header');
				chat_window.append(chat_window_header);

				var chat_window_header_name = Y.Node.create('<a></a>');
				// chat_window_header_name.setAttribute('href', server_url+'/user/profile.php?id='+user_to_id);
				chat_window_header_name.setContent(user_to_name);
				chat_window_header.append(chat_window_header_name);

				// Window Actions
				var chat_window_header_actions = Y.Node.create('<div></div>');
				chat_window_header_actions.addClass('actions');
				chat_window_header.append(chat_window_header_actions);
				
				let minimizeBtn = Y.Node.create('<svg xmlns="http://www.w3.org/2000/svg" class="minimize" width="24" height="24" fill="white" viewBox="0 0 16 16"><path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8z"/></svg>');
				let maximizeBtn = Y.Node.create('<svg xmlns="http://www.w3.org/2000/svg" style="display:none;" class="maximize" width="16" height="16" fill="white" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M5.828 10.172a.5.5 0 0 0-.707 0l-4.096 4.096V11.5a.5.5 0 0 0-1 0v3.975a.5.5 0 0 0 .5.5H4.5a.5.5 0 0 0 0-1H1.732l4.096-4.096a.5.5 0 0 0 0-.707zm4.344-4.344a.5.5 0 0 0 .707 0l4.096-4.096V4.5a.5.5 0 1 0 1 0V.525a.5.5 0 0 0-.5-.5H11.5a.5.5 0 0 0 0 1h2.768l-4.096 4.096a.5.5 0 0 0 0 .707z"/></svg>');
				chat_window_header_actions.append(minimizeBtn);
				chat_window_header_actions.append(maximizeBtn);
				
				// Window Messages
				var chat_window_messages = Y.Node.create('<div></div>');
				chat_window_messages.addClass('messages');
				chat_window.append(chat_window_messages);

				// Window Reply
				var chat_window_reply = Y.Node.create('<div></div>');
				chat_window_reply.addClass('reply');
				chat_window.append(chat_window_reply);

				var chat_window_reply_form = Y.Node.create('<form></form>');
				chat_window_reply.append(chat_window_reply_form);

				var chat_window_reply_text = Y.Node.create('<input type="text"></input>');
				chat_window_reply_text.addClass('chat_window_insert_text');
				chat_window_reply_form.append(chat_window_reply_text);

				var chat_window_reply_button = Y.Node.create('<input type="submit"></input>');
				chat_window_reply_button.addClass('chat_window_submit_text');
				chat_window_reply_button.setAttribute('value', 'Senden');
				chat_window_reply_form.append(chat_window_reply_button);


				// Set Actions
				var minimize = chat_window_header_actions.one('.minimize');
				var maximize = chat_window_header_actions.one('.maximize');
				//var close = chat_window_header_actions.one('.close');

				let that = this;

				// Minimize Event
				minimize.on('click', function(event) {
					chat_window.addClass('collapsed');
					this.setStyle('display', 'none');
					maximize.setStyle('display', 'block');
					that.is_minimized = true;
					localStorage.setItem("chatbot.minimized", "true");
				});

				// // Maximize Event
				maximize.on('click', function(event) {
					chat_window.removeClass('collapsed');
					this.setStyle('display', 'none');
					minimize.setStyle('display', 'block');
					that.is_minimized = false;
					localStorage.setItem("chatbot.minimized", "false");
				});

				if(this.is_minimized) {
					minimize.setStyle('display', 'none');
					maximize.setStyle('display', 'block');
				}

				// // Close Event
				// close.on('click', function(event) {

				// 	var params = {};
				// 	params.session_id = session_id;
				// 	params.user_id = self.user.id;

				// 	self.send_data(Y, conn, 'close_session', params);

				// 	var current_window = chat_window;
				// 	chat_window_width = parseInt(current_window.getStyle('width'));

				// 	while( current_window = current_window.next('.chat_window') ) {
				// 		var current_window_right = parseInt(current_window.getStyle('right'));
				// 		current_window.setStyle('right', (current_window_right - chat_window_width - self.space_between_windows) + 'px');
				// 	}

				// 	chat_window.remove(true);
				// });

				// Input Text Focus Event
				chat_window_reply_text.on('focus', function(event) {
					self.setAllSeen(Y, conn, chat_window, session_id);
				});
				chat_window_reply_text.on('textInput', function(event) {
					self.setAllSeen(Y, conn, chat_window, session_id);
				});

				// Submit Message
				chat_window_reply_form.on('submit', function(event) {
					event.preventDefault();

					var message = chat_window_reply_text.get('value');

					if(message.length > 0) {
						var params = {};
						params.session = {};
						params.session.id = session_id;
						params.session.unseen = 1;
						params.user = self.user;
						params.message = {};
						params.message.text = message;
						params.message.userid = self.user.id;

						self.post_message(Y, conn, server_url, params);
						chat_window_reply_text.set('value', '');
					}
				});
			}

			// Creates all messages
			// if(params.messages) {
			// 	var msg_params = {};
			// 	msg_params.session = params.session;

			// 	for(i in params.messages) {
			// 		var msg = params.messages[i];
			// 		if(msg.userid == params.userfrom.id) {
			// 			msg_params.user = params.userfrom;
			// 		}
			// 		else {
			// 			msg_params.user = params.userto;
			// 		}
			// 		msg_params.message = msg;

			// 		this.create_message(Y, chat_window_messages, msg_params);
			// 		this.messages_scroll_bottom(Y, chat_window_messages);
			// 	}
			// }
		}

		// If the user which opened the session isn't the same as current user
		// if(params.userto.id != this.user.id && focus) {
		// 	// Focus input text
		// 	chat_window.one('.chat_window_insert_text').focus();
		// }

		// // If session as unseen messages
		// else if(parseInt(params.session.unseen)) {
		// 	chat_window.addClass('new_messages');
		// }
	},
	
	
	/**
	 * Posts a message to a session by sending the data to the server and by adding it to the chat window.
	 * If the message was received from other user, it first starts or updates the session 
	 * and then adds the message to the chat window.
	 * @param object Y - YUI 3 object.
	 * @param object conn - the connection with the server.
	 * @param string server_url - the server url.
	 * @param object params - the data sent by the server.
	 */
	post_message : function(Y, conn, server_url, params) {
		if(params.session && params.message) {
			if(params.message.userid == this.user.id) {
				this.send_data(Y, conn, 'post_message', params);
			}
			// else {
			// 	var session_params = {};
			// 	session_params.session = params.session;
			// 	session_params.userto = params.user;
			// 	this.start_session(Y, conn, server_url, session_params, false);
			// }

			//Create Message
			params.message = params.message.text;
			params.format = "text";
			var chat_window_messages = Y.one('#chatbot_session_'+params.session.id).one('.messages');
			this.create_message(Y, chat_window_messages, params);

			//Scroll to Bottom
			this.messages_scroll_bottom(Y, chat_window_messages);
		}
	},


	/**
	 * Sets all messages as seen by notifying the server and by removing the class.
	 * @param object Y - YUI 3 object.
	 * @param object conn - the connection with the server.
	 * @param object chat_window - the chat window DOM.
	 * @param int session_id - the session id.
	 */
	setAllSeen : function(Y, conn, chat_window, session_id) {
		if(chat_window.hasClass('new_messages')) {
			
			//Set all messages seen
			var params = {
				session_id : session_id,
				user_id : this.user.id
			};
			this.send_data(Y, conn, 'seen_session', params);

			chat_window.removeClass('new_messages');
		}
	},


	/**
	 * Creates a message HTML to add to a chat window.
	 * @param object Y - YUI 3 object.
	 * @param Node chat_window_messages - the DOM element which contains the session messages.
	 * @param object params - the data sent by the server.
	 */
	create_message : function(Y, chat_window_messages, params) {
		//Get last user to post
		var chat_window_last_user = chat_window_messages.all('> .user').slice(-1).item(0);

		var last_user = null;
		if(chat_window_last_user) {
			last_user = chat_window_last_user.getAttribute('class').toString().split(' ')[1];
			last_user = last_user.split('_')[2];
		}

		//if(last_user != params.user.id) {
		if(last_user) {
			//Create Users Separator
			var chat_user_separator = Y.Node.create('<div></div>');
			chat_user_separator.addClass('border');
			chat_window_messages.append(chat_user_separator);
		}

		//Create User
		var chat_user = Y.Node.create('<div></div>');
		chat_user.addClass('user chatbot_user_'+params.user.id);
		chat_user.addClass("username");
		chat_user.setContent(params.user.username+':');
		chat_window_messages.append(chat_user);
		// }

		//Create Message
		if(params.format === "text")
		{
			// normal text
			var chat_message = Y.Node.create('<p></p>');
			chat_message.addClass('message');
			chat_message.setContent(params.message);
			chat_window_messages.append(chat_message);
		} else if(params.format === "html")
		{
			// html content, e.g. iframe with h5p content
			var chat_message = Y.Node.create(params.message);
			// chat_message.addClass('message');
			chat_window_messages.append(chat_message);
		}
		//Create Break Line
		var chat_br = Y.Node.create('<br/>');
		chat_window_messages.append(chat_br);
	},


	/**
	 * Scroll the messages DOM element to the bottom.
	 * @param object Y - YUI 3 object.
	 * @param Node container - the DOM element which contains the session messages.
	 */
	messages_scroll_bottom : function(Y, container) {
		container.set('scrollTop', container.get('scrollHeight')-parseInt(container.getStyle('height')));
	},


	/**
	 * Compares two strings.
	 * @param string str1 - first string.
	 * @param string str2 - second string.
	 * @return bool result - 0 if strings are equal, -1 if str1 < str 2, or 1 if str1 > str2.
	 */
	strcmp : function(str1, str2) {
		return ( ( str1 == str2 ) ? 0 : ( ( str1 > str2 ) ? 1 : -1 ) );
	},


	/**
	 * Creates the empty chat message.
	 * @param object Y - YUI 3 object.
	 * @param Node container - the DOM element which contains the online users.
	 */
	empty : function(Y, container) {
		var empty_item = Y.Node.create('<li class="empty"></li>');
		var item_div = Y.Node.create('<div class="column c1"></div>');
		item_div.setContent(M.util.get_string('no-users', 'block_chatbot'));
		empty_item.append(item_div);
		container.setContent(empty_item);
	},


	/**
	 * Creates a chat error message.
	 * @param object Y - YUI 3 object.
	 * @param string message - the message code.
	 */
	error : function(Y, message) {
		var block_content = Y.one('.block_'+this.name).one('.unlist');
		var list_item = Y.Node.create('<li></li>');
		var item_div = Y.Node.create('<div class="notifyproblem"></div>');
		item_div.setContent(M.util.get_string(message, 'block_chatbot'));
		list_item.append(item_div);
		block_content.setContent(list_item);
	}
	
};