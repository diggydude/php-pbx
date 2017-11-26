#include <Messenger.h>

const byte TONE_NONE    = 0;
const byte TONE_DIAL    = 1;
const byte TONE_RINGING = 2;
const byte TONE_BUSY    = 3;
const byte TONE_REORDER = 4;

byte relayPin      = 13;
byte toneSelPin0   = 12;
byte toneSelPin1   = 11;
byte toneSelPin2   = 10;
byte toneSelPin3   = 9;
byte toneEnablePin = 8;
byte muxChSelPin0  = 7;
byte muxChSelPin1  = 6;
byte muxChSelPin2  = 5;
byte muxInhibitPin = 4;
byte currentTone   = TONE_NONE;
int  cadenceState  = -1;
long lastChanged   = 0;
Messenger message  = Messenger();

struct Cadence {
  unsigned int onTime;
  unsigned int offTime;
};

Cadence cadences[5] = {
  {0,     65535}, // none
  {65535, 0},     // dial
  {500,   500},   // busy
  {2000,  4000},  // ringing
  {250,   250}    // reorder
};

void messageComplete()
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
  else if (message.checkString("TONE")) {
    setTone(message.readInt());
  }
} // messageCompolete

void connect(byte channel)
{
  digitalWrite(muxChSelPin0,  bitRead(channel, 0));
  digitalWrite(muxChSelPin1,  bitRead(channel, 1));
  digitalWrite(muxChSelPin2,  bitRead(channel, 2));
  digitalWrite(muxInhibitPin, HIGH);
  lastChanged = millis();
} // connect

void disconnect()
{
  digitalWrite(muxInhibitPin, LOW);
  setTone(TONE_NONE);
} // disconnect

void setTone(byte tone)
{
  switch (tone) {
    case TONE_DIAL:
      digitalWrite(toneSelPin0, LOW);
      digitalWrite(toneSelPin1, LOW);
      digitalWrite(toneSelPin2, LOW);
      digitalWrite(toneSelPin3, LOW);
      cadenceState = 1;
      break;
    case TONE_BUSY:
      digitalWrite(toneSelPin0, LOW);
      digitalWrite(toneSelPin1, HIGH);
      digitalWrite(toneSelPin2, HIGH);
      digitalWrite(toneSelPin3, LOW);
      cadenceState = 1;
      break;
    case TONE_RINGING:
      digitalWrite(toneSelPin0, LOW);
      digitalWrite(toneSelPin1, LOW);
      digitalWrite(toneSelPin2, HIGH);
      digitalWrite(toneSelPin3, HIGH);
      cadenceState = 1;
      break;
    case TONE_REORDER:
      digitalWrite(toneSelPin0, LOW);
      digitalWrite(toneSelPin1, HIGH);
      digitalWrite(toneSelPin2, HIGH);
      digitalWrite(toneSelPin3, LOW);
      cadenceState = 1;
      break;
    case TONE_NONE:
    default:
      cadenceState = -1;
      break;
  }
} // setTone

void setup()
{
  Serial.begin(9600);
  message.attach(messageCompleted);
  pinMode(relayPin,           OUTPUT);
  pinMode(toneSelPin0,        OUTPUT);
  pinMode(toneSelPin1,        OUTPUT);
  pinMode(toneSelPin2,        OUTPUT);
  pinMode(toneSelPin3,        OUTPUT);
  pinMode(toneEnablePin,      OUTPUT);
  pinMode(muxChSelPin0,       OUTPUT);
  pinMode(muxChSelPin1,       OUTPUT);
  pinMode(muxChSelPin2,       OUTPUT);
  pinMode(muxInhibitPin,      OUTPUT);
  digitalWrite(toneEnablePin, HIGH);
  digitalWrite(muxInhibitPin, LOW);
  delay(250);
  digitalWrite(relayPin, HIGH);
} // setup

void loop()
{
  while (Serial.available()) {
    message.process(Serial.read());
  }
  if (cadenceState < 0) {
    if ((millis() - lastChanged) > cadences[currentTone].offTime) {
      cadenceState = -cadenceState;
      lastChanged  = millis();
      digitalWrite(toneEnablePin, LOW);
    }
  }
  else {
    if ((millis() - lastChanged) > cadences[currentTone].onTime) {
      cadenceState = -cadenceState;
      lastChanged  = millis();
      digitalWrite(toneEnablePin, HIGH);
    }
  }
} // loop

