import json, sys, time, base64, urllib.request
from websocket import create_connection

port, url, out = sys.argv[1], sys.argv[2], sys.argv[3]
w = int(sys.argv[4]) if len(sys.argv) > 4 else 1440

tabs = json.load(urllib.request.urlopen(f'http://127.0.0.1:{port}/json'))
ws = create_connection(tabs[0]['webSocketDebuggerUrl'], timeout=120)
mid = 0

def send(m, p=None):
    global mid
    mid += 1
    ws.send(json.dumps({'id': mid, 'method': m, 'params': p or {}}))
    while True:
        r = json.loads(ws.recv())
        if r.get('id') == mid:
            return r

send('Page.enable')
send('Emulation.setDeviceMetricsOverride', {'width': w, 'height': 1000, 'deviceScaleFactor': 1, 'mobile': w < 600})
send('Page.navigate', {'url': url})
time.sleep(8)
send('Runtime.evaluate', {'expression': 'window.scrollTo(0,document.body.scrollHeight)'})
time.sleep(2)
send('Runtime.evaluate', {'expression': 'window.scrollTo(0,0)'})
time.sleep(1)
h = send('Runtime.evaluate', {'expression': 'document.documentElement.scrollHeight', 'returnByValue': True})['result']['result']['value']
send('Emulation.setDeviceMetricsOverride', {'width': w, 'height': int(h) + 20, 'deviceScaleFactor': 1, 'mobile': w < 600})
time.sleep(1.5)
r = send('Page.captureScreenshot', {'format': 'png', 'captureBeyondViewport': True})
open(out, 'wb').write(base64.b64decode(r['result']['data']))
print('captured', out, 'height', h)
ws.close()
