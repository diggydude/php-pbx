#include <Messenger.h>

byte waveOutPin      = 13;
byte muxChSelPin0    = 12;
byte muxChSelPin1    = 11;
byte muxChSelPin2    = 10;
byte muxInhibitPin   = 9;
byte ringModePins[8] = {8, 7, 6, 5, 4, 3, 2, 1};
byte currentChannel  = 0;
byte i               = 0;
int  waveState       = -1
long lastChanged     = 0;

void messageCompleted()
{
  if (message.checkString("ID?")) {
    Serial.println(id);
  }
  else if (message.checkString("CONNECT")) {
    connect(message.readInt());
  }
  else if (message.checkString("DISCONNECT")) {
    disconnect();
  }
} // messageCompleted

void connect(byte channel)
{
  currentChannel = channel;
  digitalWrite(muxChSelPin0,          bitRead(channel, 0));
  digitalWrite(muxChSelPin1,          bitRead(channel, 1));
  digitalWrite(muxChSelPin2,          bitRead(channel, 2));
  digitalWrite(ringModePins[channel], HIGH);
  digitalWrite(muxInhibitPin,         HIGH);
} // connect

void disconnect()
{
  digitalWrite(ringModePins[currentChannel], LOW);
  digitalWrite(muxInhibitPin,                LOW);
} // disconnect

void setup()
{
  Serial.begin(9600);
  message.attach(messageCompleted);
  pinMode(waveOutPin,         OUTPUT);
  pinMode(muxSelPin0,         OUTPUT);
  pinMode(muxSelPin1,         OUTPUT);
  pinMode(muxSelPin2,         OUTPUT);
  pinMode(muxInhibitPin,      OUTPUT);
  digitalWrite(muxInhibitPin, LOW);
  for (i = 0; i < 8; i++) {
    pinMode(ringModePins[i],      OUTPUT);
    digitalWrite(ringModePins[i], LOW);
  }
} // setup

void loop()
{
  while (Serial.available()) {
    message.process(Serial.read());
  }
  if (ringState < 0) {
    if ((millis() - lastChanged) > 20) {
      ringState   = -ringState;
      lastChanged = millis();
      digitalWrite(waveOutPin, HIGH);
    }
  }
  else {
    if ((millis() - lastChanged) > 20) {
      ringState   = -ringState;
      lastChanged = millis();
      digitalWrite(waveOutPin, LOW);
    }
  }
} // loop
