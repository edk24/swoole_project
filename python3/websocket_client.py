import websocket
import requests
import _thread
import json
from urllib import parse

# 事件_收到数据
def on_message(ws, message):
    ws.send(11)
    pass

# 事件_连接错误
def on_error(ws, error):
    pass

# 事件_连接断开
def on_close(ws):
    pass

# 事件_连接成功
def on_open(ws):
    pass

def connent_php():
    print(1)

if __name__ == "__main__":
    websocket.enableTrace(True)
    _thread.start_new_thread(connent_php, ())
    ws = websocket.WebSocketApp("wss://api.ifukang.com/v2/ws",
                                on_message=on_message,
                                on_error=on_error,
                                on_close=on_close, on_open=on_open)
    ws.run_forever()