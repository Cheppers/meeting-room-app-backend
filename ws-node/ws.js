var server = require('http').Server();
var io = require('socket.io')(server);
var Redis = require('ioredis');
require('dotenv').config({path: './.env'});

var redis = new Redis('redis://' + process.env.REDIS_HOST + ":" + process.env.REDIS_PORT);

var request = require('request');

redis.psubscribe('ws-*', function(err, count) {
    console.log('Subscribed to ws-* redis channels');
});

redis.on('pmessage', function(subscribed, room, message) {
    console.log('Msg to room |' + room + '|: ' + message);

    try {
        message = JSON.parse(message);
    } catch (ex) {
        message = {"event":"ws-error","data":"JSON parse error"};
    }

    io.to(room).emit(message.event, message.data);
});

io.on('connection', function (socket) {
    console.log('Connected...');

    socket.on('join', function(room) {
        socket.join(room);
        console.log('Joined room |' + room + '|');
    });

    socket.on('leave', function(room) {
        socket.leave(room);
        console.log('Left room |' + room + '|');
    });

    socket.on('refresh', function(room) {
        console.log('Refresh room |ws-' + room + '|');

        let options = {
            url: process.env.PRIVATE_URL + '/api/event/refresh/' + room,
            method: 'GET',
            headers: {
                Authorization: 'Bearer ' + process.env.USER_TOKEN
            }
        };

        request(options, function (error, response, body) {
            if (!error && response.statusCode == 200) {
                console.log('Refresh room |ws-' + room + '|: ok');
            } else {
                console.log('Refresh room |ws-' + room + '|: error');
            }
        });
    });
});

server.listen(process.env.PORT);

console.log('Listen...');
