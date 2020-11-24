(function () {
  const name = window.prompt('请输入你的名字')
  let avatar = parseInt(window.prompt('选择你的头像 可以为 1 2 3 4'))
  avatar = avatar % 4 +1
  avatar = 't' + avatar + '.png'
  if (name && name !== '') {
    $('#name').html(name)
    $('#tips').html('共有0条消息')
    console.log(avatar)
    $('#avatar').attr('src','img/' + avatar)
    const ws = new WebSocket('ws://192.168.0.121:9502')
    ws.onopen = function () {
      // Web Socket 已连接上，使用 send() 方法发送数据
      let data = {
        msg: name + ' 加入',
        data:{
          user: {
            name: name,
            avatar: avatar
          }
        },
        code: 2
      }
      ws.send(JSON.stringify(data))
    };
    ws.onmessage = function (evt) {
      var received_msg = evt.data;
      let data = JSON.parse(received_msg)
      if (data.code === 1) {
        chat.messageResponses = data.msg
        // responses
        var templateResponse = Handlebars.compile($("#message-response-template").html());
        var contextResponse = {
          response: chat.messageResponses,
          time: chat.getCurrentTime(),
          name:data.from.name
        };
        chat.$chatHistoryList.append(templateResponse(contextResponse));
        chat.scrollToBottom()
      } else if (data.code === 2) {
        chat.addUser(data.data.user)
      }else if (data.code === 3) {
        chat.addUser(data.data.users)
      }

    };
    ws.onclose = function () {
      // 关闭 websocket
      console.log("连接已关闭...");
    };
    var chat = {
      messageToSend: '',
      messageResponses: '',
      people: $('#people'),
      init: function () {
        this.cacheDOM();
        this.bindEvents();
        this.render();
      },
      cacheDOM: function () {
        this.$chatHistory = $('.chat-history');
        this.$button = $('button');
        this.$textarea = $('#message-to-send');
        this.$chatHistoryList = this.$chatHistory.find('ul');
      },
      bindEvents: function () {
        this.$button.on('click', this.addMessage.bind(this));
        this.$textarea.on('keyup', this.addMessageEnter.bind(this));
      },
      render: function () {
        if (this.messageToSend.trim() !== '') {
          var template = Handlebars.compile($("#message-template").html());
          var context = {
            messageOutput: this.messageToSend,
            time: this.getCurrentTime()
          };
          this.$chatHistoryList.append(template(context));
          this.scrollToBottom();
          this.$textarea.val('');
        }

      },
      addMessage: function () {
        this.messageToSend = this.$textarea.val()
        let data = {msg: this.messageToSend}
        ws.send(JSON.stringify(data))
        this.render();
      },
      addMessageEnter: function (event) {
        // enter was pressed
        if (event.keyCode === 13) {
          this.addMessage();
        }
      },
      scrollToBottom: function () {
        this.$chatHistory.scrollTop(this.$chatHistory[0].scrollHeight);
      },
      getCurrentTime: function () {
        return new Date().toLocaleTimeString().replace(/([\d]+:[\d]{2})(:[\d]{2})(.*)/, "$1$3");
      },
      addUser: function (users) {
        this.people.empty()
        let userHTML = ''
        for (let i = 0; i < users.length; i++) {
           userHTML += `<li class="clearfix">
                <img src="img/${users[i].avatar}" alt="avatar" />
                <div class="about">
                    <div class="name">${users[i].name}</div>
                    <div class="status">
                        <i class="fa fa-circle online"></i> online
                    </div>
                </div>
              </li>`
        }
        this.people.append(userHTML)
      }
    };
    chat.init();

    var searchFilter = {
      options: {valueNames: ['name']},
      init: function () {
        var userList = new List('people-list', this.options);
        var noItems = $('<li id="no-items-found">No items found</li>');

        userList.on('updated', function (list) {
          if (list.matchingItems.length === 0) {
            $(list.list).append(noItems);
          } else {
            noItems.detach();
          }
        });
      }
    };
    // searchFilter.init();
  } else {
    window.alert('你的名字无效')
    location.reload()
  }
})();
