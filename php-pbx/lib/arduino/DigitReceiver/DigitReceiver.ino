#include <Messenger.h>

const byte STATUS_READY     = 0;
const byte STATUS_WAITING   = 1;
const byte STATUS_RECEIVING = 2;
const byte STATUS_COMPLETED = 3;

char id[14]         = "digitreceiver";
byte muxInhibitPin  = 12;
byte muxComIoPin    = 11;
byte muxChSelAPin   = 8;
byte muxChSelBPin   = 9;
byte muxChSelCPin   = 10;
byte i;
int  count          = 0;
int  digitCaptured  = 0;
int  lastState      = LOW;
int  trueState      = LOW;
long lastChange     = 0;
int  cleared        = 0;
int  dialTimeout    = 100;
int  debounceDelay  = 10;
byte currentDigit   = 0;
byte digits[4]      = {0, 0, 0, 0};
byte status         = STATUS_READY;
Messenger messenger = Messenger();

void messageCompleted()
{
  if (message.checkString("ID?")) {
    Serial.println(id);
  }
  else if (message.checkString("STATUS?")) {
    Serial.println(status);
  }
  else if (message.checkString("DIGITS?")) {
    for (i = 0; i < 4; i++) {
      Serial.print(digits[i], DEC);
    }
    Serial.println("");
    status = STATUS_READY;
  }
  else if (message.checkString("CONNECT")) {
    connect(message.readInt());
    status = STATUS_WAITING;
  }
  else if (message.checkString("RESET")) {
    disconnect();
    reset();
  }
} // messageComoleted

void connect(byte channel)
{
  digitalWrite(muxChSelAPin,  bitRead(channel, 0));
  digitalWrite(muxChSelBPin,  bitRead(channel, 1));
  digitalWrite(muxChSelCPin,  bitRead(channel, 2));
  digitalWrite(muxInhibitPin, HIGH);
} // connect

void disconnect()
{
  digitalWrite(muxInhibitPin, LOW);
} // disconnect

void reset()
{
  count         = 0;
  digitCaptured = 0;
  lastState     = LOW;
  trueState     = LOW;
  lastChange    = 0;
  cleared       = 0;
  currentDigit  = 0;
  for (i = 0; i < 4; i++) {
    digits[i] = 0;
  }
  status = STATUS_READY;
} // reset

void setup()
{
  Serial.begin(9600);
  message.attach(messageCompleted);
  pinMode(muxInhibitPin, OUTPUT);
  pinMode(muxChSelAPin,  OUTPUT);
  pinMode(muxChSelBPin,  OUTPUT);
  pinMode(muxChSelCPin,  OUTPUT);
  pinMode(muxComIoPin,   INPUT);
} // setup

void loop()
{
  while (Serial.available()) {
    message.process(Serial.read());
  }
  int reading = digitalRead(muxComIoPin);
  if ((millis() - lastChange) > dialTimeout) {
    if (digitCaptured) {
      digits[currentDigit] = count % 10;
      digitCaptured        = 0;
      count                = 0;
      cleared              = 0;
      status               = STATUS_RECEIVING;
      if (currentDigit < 3) {
        currentDigit++;
      }
      else {
        disconnect();
        status       = STATUS_COMPLETED;
        currentDigit = 0;
      }
    }
  }
  if (reading != lastState) {
    lastChange = millis();
  }
  if ((millis() - lastChange) > debounceDelay) {
    if (reading != trueState) {
      trueState = reading;
      if (trueState == HIGH) {
        count++;
        digitCaptured = 1;
      }
    }
  }
  lastState = reading;
} // loop
