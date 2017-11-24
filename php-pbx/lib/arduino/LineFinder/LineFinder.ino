#include <Wire.h>
#include <Messenger.h>

char id[11]        = "linefinder";
byte i             = 0;
byte currentPin    = 0;
byte currentState  = LOW;
byte pins[]        = {5, 6, 7, 8, 9, 10, 11, 12};
byte states[]      = {-1, -1, -1, -1, -1, -1, -1, -1};
byte status[]      = {0, 0, 0, 0, 0, 0, 0, 0};
long lastChecked[] = {0, 0, 0, 0, 0, 0, 0, 0};
long debounceDelay = 10;
byte result        = 0;
Messenger message  = Messenger();

void next()
{
  currentPin = (currentPin < 7) ? (currentPin + 1) : 0;
} // next

void messageCompleted()
{
  if (message.checkString("ID?")) {
    Serial.println(id);
  }
  else if (message.checkString("STATUS?")) {
    result = 0;
    for (i = 0; i < 8; i++) {
      if (status[i] == 1) {
        bitSet(result, i);
      }
    }
    Serial.println(result);
  }
} // messageCompleted

void setup()
{
  Wire.begin();
  Serial.begin(9600);
  message.attach(messageCompleted);
  for (i = 0; i < 8; i++) {
    pinMode(pins[i], INPUT);
  }
} // setup

void loop()
{
  while (Serial.available()) {
    message.process(Serial.read());
  }
  currentState = digitalRead(pins[currentPin]);
  if ((millis() - lastChecked[currentPin]) > debounceDelay) {
    if ((currentState == HIGH) && (states[currentPin] < 0)) {
      status[currentPin]      = 1;
      states[currentPin]      = -states[currentPin];
      lastChecked[currentPin] = millis();
    }
    else if ((currentState == HIGH) && (states[currentPin] > 0)) {
      status[currentPin]      = 0;
      states[currentPin]      = -states[currentPin];
      lastChecked[currentPin] = millis();
    }
  }
  next();
} // loop

