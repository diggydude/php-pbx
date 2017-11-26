#include <Messenger.h>

char id[7]     = "switch";
byte axPin0    = 13;
byte axPin1    = 12;
byte axPin2    = 11;
byte ayPin0    = 10;
byte ayPin1    = 9;
byte ayPin2    = 8;
byte strobePin = 7;
byte dataPin   = 6;
byte resetPin  = 5;
Messenger message = Messenger();

void connect(byte caller, byte callee)
{
  digitalWrite(resetPin,  LOW);
  digitalWrite(axPin0,    bitRead(caller, 0));
  digitalWrite(axPin1,    bitRead(caller, 1));
  digitalWrite(axPin2,    bitRead(caller, 2));
  digitalWrite(ayPin0,    bitRead(callee, 0));
  digitalWrite(ayPin1,    bitRead(callee, 1));
  digitalWrite(ayPin2,    bitRead(callee, 2));
  digitalWrite(dataPin,   HIGH);
  digitalWrite(strobePin, HIGH);
  delay(1);
  digitalWrite(strobePin, LOW);
} // connect

void disconnect(byte caller, byte callee)
{
  digitalWrite(resetPin,  LOW);
  digitalWrite(axPin0,    bitRead(caller, 0));
  digitalWrite(axPin1,    bitRead(caller, 1));
  digitalWrite(axPin2,    bitRead(caller, 2));
  digitalWrite(ayPin0,    bitRead(callee, 0));
  digitalWrite(ayPin1,    bitRead(callee, 1));
  digitalWrite(ayPin2,    bitRead(callee, 2));
  digitalWrite(dataPin,   LOW);
  digitalWrite(strobePin, HIGH);
  delay(1);
  digitalWrite(strobePin, LOW);
} // disconnect

void reset()
{
  digitalWrite(resetPin, HIGH);
  delay(1);
  digitalWrite(resetPin, LOW);
} // reset

void messageCompleted()
{
  if (message.checkString("ID?")) {
    Serial.println(id);
  }
  else if (message.checkString("RESET")) {
    reset();
  }
  else if (message.checkString("CONNECT")) {
    connect(message.readInt(), message.readInt());
  }
  else if (message.checkString("DISCONNECT")) {
    disconnect(message.readInt(), message.readInt());
  }
} // messageCompleted

void setup()
{
  Serial.begin(9600);
  message.attach(messageCompleted);
  pinMode(axPin0,    OUTPUT);
  pinMode(axPin1,    OUTPUT);
  pinMode(axPin2,    OUTPUT);
  pinMode(ayPin0,    OUTPUT);
  pinMode(ayPin1,    OUTPUT);
  pinMode(ayPin2,    OUTPUT);
  pinMode(resetPin,  OUTPUT);
  pinMode(dataPin,   OUTPUT);
  pinMode(strobePin, OUTPUT);
  reset();
} // setup

void loop()
{
  while (Serial.available()) {
    message.process(Serial.read());
  }
} // loop

