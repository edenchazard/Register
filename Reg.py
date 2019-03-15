#!/usr/bin/env python
# -*- coding: utf8 -*-
import RPi.GPIO as GPIO
import MFRC522
import signal
import urllib2
import time
import ConfigParser
import os
import spi
import logging

#### Globals
CONTINUE_READ   = True
CFG             = { "ID": None, "SITE": None, "APIKEY": None }
# Pins
RED				= 37
GREEN			= 38
BLUE			= 40
# Files
CONF            = "/home/pi/Register/MFRC522-python/config.ini"
LOG             = "/home/pi/Register/MFRC522-python/log.log"

def setup():
    global CFG
    logging.basicConfig(filename=LOG, format='%(asctime)s %(message)s', level=logging.DEBUG)

    
    # gpio setup
    GPIO.setwarnings(False)
    GPIO.setmode(GPIO.BOARD)
    GPIO.setup(RED, GPIO.OUT); GPIO.output(RED, GPIO.LOW)
    GPIO.setup(GREEN, GPIO.OUT); GPIO.output(GREEN, GPIO.LOW)
    GPIO.setup(BLUE, GPIO.OUT); GPIO.output(BLUE, GPIO.LOW)

    # Hook the SIGINT
    signal.signal(signal.SIGINT, end_read)

    # config setup
    logging.info("Reading config file")
    cfg = ConfigParser.ConfigParser()
    cfg.read(CONF)
    logging.info("Finished reading config file")
    CFG = { "ID": cfg.get('register', 'id'), "SITE": cfg.get('register', 'site'), "APIKEY": cfg.get('register', 'apikey') }

# Blinking: error
def flashing_light():
    for i in range(3):
		GPIO.output(BLUE, GPIO.HIGH)
		time.sleep(0.5)
		GPIO.output(BLUE, GPIO.LOW)
		time.sleep(0.5)

# Pretty colours
def light(colour):
	GPIO.output(colour, GPIO.HIGH)
	time.sleep(1)
	GPIO.output(colour, GPIO.LOW)

# Capture SIGINT for cleanup when the script is aborted
def end_read(signal, frame):
    global CONTINUE_READ
    logging.info("Ctrl+C captured, ending read.")
    CONTINUE_READ = False
    GPIO.cleanup()

def doSign(uid):
    api = "http://register.opqua.com/rfid/rfid.php?uid={}&id={}&site={}&apikey={}".format(uid, CFG['ID'], CFG['SITE'], CFG['APIKEY'])
    logging.info("Contacting server")

    # Attempt a connection to the server
    try:
        f = urllib2.urlopen(api, timeout=1.5)
        result = f.read()

        if result == "LOGGED_IN":
            logging.info("SI #"+uid)
            light(GREEN)
        elif result == "LOGGED_OUT":
            logging.info("SO #"+uid)
            light(RED)
        # Unwanted response, emit blue
        else:
            logging.warn(result + ": #"+uid)
            light(BLUE)
    except Exception as e:
        logging.exception("")
        flashing_light()
    finally:
        try:
            f.close()
        except UnboundLocalError:
            pass

def main():
    setup()

    logging.info("RFID scanner started. CTRL+C to stop execution.")

    # bugfix: create a new mfrc instance every iter.
    # The scanner randomly stops reading cards but this seems to solve it
    # along with a gpio flush
    # Create an object of the class MFRC522
    # This loop keeps checking for chips. If one is near it will get the UID and authenticate
    MIFAREReader = MFRC522.MFRC522()
    while CONTINUE_READ:
        try:
            # Scan for cards
            (status, TagType) = MIFAREReader.MFRC522_Request(MIFAREReader.PICC_REQIDL)

            # If a card is found
            if status == MIFAREReader.MI_OK:
                pass #logging.info("Card detected") #uncommented for now to prevent log bloat

            # Get the UID of the card
            (status,uid) = MIFAREReader.MFRC522_Anticoll()
            #logging.info(str(status))

            # If we have the UID, continue
            if status == MIFAREReader.MI_OK:
                UID = str(uid[0])+str(uid[1])+str(uid[2])+str(uid[3])

                # Print UID
                logging.info("UID #"+UID)

                # This is the default key for authentication
                key = [0xFF,0xFF,0xFF,0xFF,0xFF,0xFF]

                # Select the scanned tag
                MIFAREReader.MFRC522_SelectTag(uid)

                # Authenticate
                status = MIFAREReader.MFRC522_Auth(MIFAREReader.PICC_AUTHENT1A, 8, key, uid)

                # Check if authenticated
                if status == MIFAREReader.MI_OK:
                    MIFAREReader.MFRC522_Read(8)
                    MIFAREReader.MFRC522_StopCrypto1()
                    doSign(UID)
                else:
                    light(BLUE)
                    logging.error("Authentication error")

        # Any exceptions, just emit a flashing light
        except Exception as e:
            logging.exception("")
            flashing_light()

        MIFAREReader.MFRC522_Init()    
        # clean up the RST pin mrfc uses and leave others active
        #GPIO.cleanup([22]) #[24, 23, 19, 21, 6, 22, 1]
        # important to do before we create a new mfrc instance
        # or we get 'too many files' error
        #spi.closeSPI()

    GPIO.cleanup()
    logging.critical("Exiting.")
    logging.shutdown()

main()