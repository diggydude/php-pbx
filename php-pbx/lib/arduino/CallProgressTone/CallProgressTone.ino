#include <Messenger.h>

const byte TONE_DIAL    = 0;
const byte TONE_RINGING = 1;
const byte TONE_BUSY    = 2;
const byte TONE_REORDER = 3;

char id[13]        = "callprogress";
byte relayPin      = 7;
byte toneSelPin0   = 9;
byte toneSelPin1   = 10;
byte toneSelPin2   = 11;
byte toneSelPin3   = 12;
byte toneEnablePin = 8;
byte muxChSelPin0  = 3;
byte muxChSelPin1  = 4;
byte muxChSelPin2  = 5;
byte muxInhibitPin = 6;
byte currentTone   = TONE_REORDER;
int  cadenceState  = -1;
long lastChanged   = 0;
Messenger message  = Messenger();

struct Cadence {
  unsigned int onTime;
  unsigned int offTime;
};

Cadence cadences[4] = {
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
  else if (message.checkString("MUTE")) {
    mute();
  } 
} // messageComplete

void connect(byte channel)
{
  digitalWrite(muxChSelPin0,  bitRead(channel, 0));
  digitalWrite(muxChSelPin1,  bitRead(channel, 1));
  digitalWrite(muxChSelPin2,  bitRead(channel, 2));
  digitalWrite(muxInhibitPin, LOW);
  Serial.print("Connected to: ");
  Serial.println(channel);
} // connect

void disconnect()
{
  digitalWrite(muxInhibitPin, HIGH);
  Serial.println("Disconnected");
} // disconnect

void setTone(byte tone)
{
  digitalWrite(toneEnablePin, HIGH);
  delay(250);
  switch (tone) {
    case TONE_DIAL:
      digitalWrite(toneSelPin0, LOW);
      digitalWrite(toneSelPin1, LOW);
      digitalWrite(toneSelPin2, LOW);
      digitalWrite(toneSelPin3, LOW);
      break;
    case TONE_BUSY:
      digitalWrite(toneSelPin0, LOW);
      digitalWrite(toneSelPin1, HIGH);
      digitalWrite(toneSelPin2, HIGH);
      digitalWrite(toneSelPin3, LOW);
      break;
    case TONE_RINGING:
      digitalWrite(toneSelPin0, LOW);
      digitalWrite(toneSelPin1, LOW);
      digitalWrite(toneSelPin2, HIGH);
      digitalWrite(toneSelPin3, HIGH);
      break;
    case TONE_REORDER:
      digitalWrite(toneSelPin0, LOW);
      digitalWrite(toneSelPin1, HIGH);
      digitalWrite(toneSelPin2, HIGH);
      digitalWrite(toneSelPin3, LOW);
      break;
  }
  delay(250);
  currentTone  = tone;
  cadenceState = 1;
  lastChanged  = millis();
  digitalWrite(muxInhibitPin, LOW);
  digitalWrite(toneEnablePin, LOW);
  Serial.print("Set tone to: ");
  Serial.println(tone);
} // setTone

void mute()
{
  digitalWrite(muxInhibitPin, HIGH);
  Serial.println("Muted");
} // mute

void setup()
{
  Serial.begin(9600);
  message.attach(messageComplete);
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
  digitalWrite(relayPin,      HIGH);
  setTone(TONE_REORDER);
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

