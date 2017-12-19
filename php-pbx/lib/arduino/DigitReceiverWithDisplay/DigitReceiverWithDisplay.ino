#include <Messenger.h>

#define USE_DISPLAY 1
#ifdef USE_DISPLAY
#include <Wire.h>
const byte displayBusAddr   = 0x20;
const byte segmentsDirAddr  = 0x00;
const byte segmentsDataAddr = 0x12;
const byte digitsDirAddr    = 0x01;
const byte digitsDataAddr   = 0x13;
const byte portDirInput     = 0x01;
const byte portDirOutput    = 0x00;
const byte patterns[]       = {
  B00111111, // 0
  B00000110, // 1
  B01011011, // 2
  B01001111, // 3
  B01100110, // 4
  B01101101, // 5
  B01111101, // 6
  B00000111, // 7
  B01111111, // 8
  B01101111, // 9
  B00000000  // blank
};
byte segmentPattern, digitPattern, currDigit;
#endif

const byte STATUS_READY     = 0;
const byte STATUS_WAITING   = 1;
const byte STATUS_RECEIVING = 2;
const byte STATUS_COMPLETED = 3;

char id[14]         = "digitreceiver";
byte muxInhibitPin  = 12;
byte muxComIoPin    = 11;
byte muxChSelPin0   = 8;
byte muxChSelPin1   = 9;
byte muxChSelPin2   = 10;
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
int  digits[4]      = {0, 0, 0, 0};
byte status         = STATUS_READY;
Messenger message   = Messenger();

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
  digitalWrite(muxChSelPin0,  bitRead(channel, 0));
  digitalWrite(muxChSelPin1,  bitRead(channel, 1));
  digitalWrite(muxChSelPin2,  bitRead(channel, 2));
  digitalWrite(muxInhibitPin, HIGH);
} // connect

void disconnect()
{
  digitalWrite(muxInhibitPin, LOW);
} // disconnect

#ifdef USE_DISPLAY
void resetDisplay()
{
  // Clear digits
  for (i = 0; i < 4; i++) {
    digits[i] = -1;
  }
  // Set segment port direction to output
  Wire.beginTransmission(busAddr);
  Wire.write(segmentsDirAddr);
  Wire.write(portDirOutput);
  Wite.endTransmission();
  // Turn off all segments
  Wire.beginTransmission(busAddr);
  Wire.write(segmentsDataAddr);
  // Wire.write(0x00); //common cathode
  Wire.write(0xFF); // common anode
  Wite.endTransmission();
  // Set digit port direction to output
  Wire.beginTransmission(busAddr);
  Wire.write(digitsDirAddr);
  Wire.write(portDirOutput);
  Wite.endTransmission();
  // Turn off all digits
  Wire.beginTransmission(busAddr);
  Wire.write(digitsDataAddr);
  Wire.write(0x00);
  Wite.endTransmission();
} // resetDisplay

void refreshDisplay()
{
  segmentPattern  = (digits[currDigit] == -1) ? patterns[10] : patterns[currDigit];
  segmentPattern ^= B11111111; // Comment out this line for common cathode
  digitPattern    = B00000000;
  bitSet(digitPattern, currDigit);
  Wire.beginTransmission(busAddr);
  Wire.write(segmentsDataAddr);
  Wire.write(segmentPattern);
  Wire.endTransmission();
  Wire.beginTransmission(busAddr);
  Wire.write(digitsDataAddr);
  Wire.write(digitPattern);
  Wire.endTransmission();
  currDigit = (currDigit >= 3) ? 0 : currDigit + 1;
} // refreshDisplay
#endif

void reset()
{
  count         = 0;
  digitCaptured = 0;
  lastState     = LOW;
  trueState     = LOW;
  lastChange    = 0;
  cleared       = 0;
  currentDigit  = 0;
#ifdef USE_DISPLAY
  resetDisplay();
#else
  for (i = 0; i < 4; i++) {
    digits[i] = 0;
  }
#endif
  status = STATUS_READY;
} // reset

void setup()
{
  Serial.begin(9600);
  message.attach(messageCompleted);
  pinMode(muxInhibitPin, OUTPUT);
  pinMode(muxChSelPin0,  OUTPUT);
  pinMode(muxChSelPin1,  OUTPUT);
  pinMode(muxChSelPin2,  OUTPUT);
  pinMode(muxComIoPin,   INPUT);
#ifdef USE_DISPLAY
  resetDisplay();
#endif
} // setup

void loop()
{
  while (Serial.available()) {
    message.process(Serial.read());
  }
  int reading = digitalRead(muxComIoPin);
  if ((millis() - lastChange) > dialTimeout) {
    if (digitCaptured) {
#ifdef USE_DISPLAY
      refreshDisplay();
#endif
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
