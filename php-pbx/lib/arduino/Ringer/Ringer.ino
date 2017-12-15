#include <Messenger.h>

char id[7]            = "ringer";
byte waveChSelPin0    = 5;
byte waveChSelPin1    = 6;
byte waveChSelPin2    = 7;
byte waveInhibitPin   = 8;
byte cadenceChSelPin0 = 9;
byte cadenceChSelPin1 = 10;
byte cadenceChSelPin2 = 11;
byte cadenceTogglePin = 12;
byte currentChannel   = -1;
byte i                = 0;
byte cadenceState     = -1;
long lastChanged      = millis();
Messenger message     = Messenger();

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
  digitalWrite(waveChSelPin0,    bitRead(channel, 0));
  digitalWrite(waveChSelPin1,    bitRead(channel, 1));
  digitalWrite(waveChSelPin2,    bitRead(channel, 2));
  digitalWrite(cadenceChSelPin0, bitRead(channel, 0));
  digitalWrite(cadenceChSelPin1, bitRead(channel, 1));
  digitalWrite(cadenceChSelPin2, bitRead(channel, 2));
  lastChanged = millis();
  digitalWrite(cadenceTogglePin, HIGH);
  digitalWrite(waveInhibitPin,   LOW);
  Serial.print("Connected to: ");
  Serial.println(channel);
} // connect

void disconnect()
{
  currentChannel = -1;
  digitalWrite(cadenceTogglePin, LOW);
  digitalWrite(waveInhibitPin,   HIGH);
  Serial.println("Disconnected");
} // disconnect

void setup()
{
  Serial.begin(9600);
  message.attach(messageCompleted);
  pinMode(waveChSelPin0,    OUTPUT);
  pinMode(waveChSelPin1,    OUTPUT);
  pinMode(waveChSelPin2,    OUTPUT);
  pinMode(cadenceChSelPin0, OUTPUT);
  pinMode(cadenceChSelPin1, OUTPUT);
  pinMode(cadenceChSelPin2, OUTPUT);
  pinMode(waveInhibitPin,   OUTPUT);
  pinMode(cadenceTogglePin, OUTPUT);
  lastChanged = millis();
  digitalWrite(cadenceTogglePin, LOW);
  digitalWrite(waveInhibitPin,   HIGH);
} // setup

void loop()
{
  while (Serial.available()) {
    message.process(Serial.read());
  }
  if (cadenceState < 0) {
    if ((millis() - lastChanged) > 4000) {
      cadenceState = -cadenceState;
      lastChanged  = millis();
      if (currentChannel > -1) {
        digitalWrite(cadenceTogglePin, HIGH);
        digitalWrite(waveInhibitPin,   LOW);
      }
    }
  }
  else {
    if ((millis() - lastChanged) > 2000) {
      cadenceState = -cadenceState;
      lastChanged  = millis();
      if (currentChannel > -1) {
        digitalWrite(cadenceTogglePin, LOW);
        digitalWrite(waveInhibitPin,   HIGH);
      }
    }
  }
} // loop

