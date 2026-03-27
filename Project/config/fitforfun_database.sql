SET sql_notes = 0;



/*
  Auteur       : aYSSAR
  Datum        : 2026-02-15
  Beschrijving : Database schema voor FitForFun, een sportschool die groepslessen aanbiedt. 
                De database bevat tabellen voor gebruikers, medewerkers, leden, lessen en reserveringen.
 
*/
DROP DATABASE IF EXISTS FitForFunDB;
CREATE DATABASE FitForFunDB;

USE FitForFunDB;



/*
  Auteur       : aYSSAR
  Datum        : 2026-02-15
  Beschrijving : Tabel voor de gebruikers aangemaakt. Deze tabel bevat informatie over de gebruikers van het systeem, zoals hun naam, gebruikersnaam, wachtwoord en inlogstatus.
  Opmerkingen  : Eventuele extra info of opmerkingen
*/



CREATE TABLE gebruiker (
    Id INT AUTO_INCREMENT PRIMARY KEY
    ,Voornaam VARCHAR(50) NOT NULL
    ,Tussenvoegsel VARCHAR(10)
    ,Achternaam VARCHAR(50) NOT NULL
    ,Gebruikersnaam VARCHAR(100) NOT NULL UNIQUE
    ,Wachtwoord VARCHAR(255) NOT NULL
    ,IsIngelogd TINYINT(1) NOT NULL DEFAULT 0
    ,Ingelogd DATE NULL
    ,Uitgelogd DATE NULL
    ,IsActief TINYINT(1) NOT NULL DEFAULT 1
    ,Opmerking VARCHAR(250) NULL
    ,Datumaangemaakt DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
    ,Datumgewijzigd DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
)ENGINE=InnoDB;



INSERT INTO gebruiker 
(Voornaam,Tussenvoegsel,Achternaam,Gebruikersnaam,Wachtwoord,IsIngelogd,Ingelogd,Uitgelogd,IsActief,Opmerking) 
VALUES
('Jan','','Jansen','janj','password1',0,NULL,NULL,1,'Reguliere gebruiker'),
('Sara','de','Vries','saradev','password2',0,NULL,NULL,1,NULL),
('Kees','','Bakker','keesb','password3',0,NULL,NULL,1,'Test admin'),
('Emma','','Janssen','emmaj','password4',0,NULL,NULL,1,NULL),
('Ali','','Khan','alik','password5',0,NULL,NULL,1,'Gast gebruiker');






/*
  Auteur       : aYSSAR
  Datum        : 2026-02-15
  Beschrijving : Tabel voor de rollen aangemaakt. Deze tabel bevat informatie over de verschillende rollen die gebruikers kunnen hebben binnen het systeem, zoals hun naam en of ze actief zijn.
  Opmerkingen  : Eventuele extra info of opmerkingen
*/


CREATE TABLE rol (
    Id INT AUTO_INCREMENT PRIMARY KEY
    ,GebruikerId INT NOT NULL
    ,Naam VARCHAR(100) NOT NULL
    ,IsActief TINYINT(1) NOT NULL DEFAULT 1
    ,Opmerking VARCHAR(250)
    ,Datumaangemaakt DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
    ,Datumgewijzigd DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
    ,FOREIGN KEY (GebruikerId) REFERENCES gebruiker(Id) ON DELETE CASCADE
)ENGINE=InnoDB;



INSERT INTO rol
(GebruikerId,Naam,IsActief,Opmerking)
VALUES
(1,'Lid',1,'Heeft standaard toegang tot lessen'),
(2,'Lid',1,NULL),
(3,'Administrator',1,'Kan alles beheren'),
(4,'Medewerker',1,'Beheert lessen en reserveringen'),
(5,'Gastgebruiker',1,'Beperkte toegang');




/*
  Auteur       : aYSSAR
  Datum        : 2026-02-15
  Beschrijving : Tabel voor de medewerkers aangemaakt. Deze tabel bevat informatie over de medewerkers van de sportschool, zoals hun naam, nummer, soort medewerker en of ze actief zijn.
  Opmerkingen  : Eventuele extra info of opmerkingen
*/


CREATE TABLE medewerker (
    Id INT AUTO_INCREMENT PRIMARY KEY
    ,Voornaam VARCHAR(50) NOT NULL
    ,Tussenvoegsel VARCHAR(10) NULL
    ,Achternaam VARCHAR(50) NOT NULL
    ,Nummer MEDIUMINT NOT NULL
    ,Medewerkersoort VARCHAR(20) NOT NULL
    ,IsActief TINYINT(1) NOT NULL DEFAULT 1
    ,Opmerking VARCHAR(250) NULL
    ,Datumaangemaakt DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
    ,Datumgewijzigd DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
)ENGINE=InnoDB;


INSERT INTO medewerker
(Voornaam,Tussenvoegsel,Achternaam,Nummer,Medewerkersoort,IsActief,Opmerking)
VALUES
('Tom','','de Groot',101,'Manager',1,NULL),
('Linda','','Smit',102,'Beheerder',1,NULL),
('Rick','','Jansen',103,'Diskmedewerker',1,NULL),
('Sanne','','de Boer',104,'Beheerder',1,'Ervaren medewerker'),
('Mark','','Visser',105,'Manager',1,NULL);





/*
  Auteur       : aYSSAR
  Datum        : 2026-02-15
  Beschrijving : Tabel voor de leden aangemaakt. Deze tabel bevat informatie over de leden van de sportschool, zoals hun naam, relatienummer, mobiel nummer, email en of ze actief zijn.
  Opmerkingen  : Eventuele extra info of opmerkingen
*/


CREATE TABLE lid (
    Id INT AUTO_INCREMENT PRIMARY KEY
    ,Voornaam VARCHAR(50) NOT NULL
    ,Tussenvoegsel VARCHAR(10)
    ,Achternaam VARCHAR(50) NOT NULL
    ,Relatienummer MEDIUMINT NOT NULL
    ,Mobiel VARCHAR(20) NOT NULL
    ,Email VARCHAR(100) NOT NULL UNIQUE
    ,IsActief TINYINT(1) NOT NULL DEFAULT 1
    ,Opmerking VARCHAR(250) NULL
    ,Datumaangemaakt DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
    ,Datumgewijzigd DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
)ENGINE=InnoDB;


INSERT INTO lid
(Voornaam,Tussenvoegsel,Achternaam,Relatienummer,Mobiel,Email,IsActief,Opmerking)
VALUES
('Laura','','Klein',201,'0612345678','laura@example.com',1,NULL),
('Peter','','de Wit',202,'0623456789','peter@example.com',1,NULL),
('Sofie','','van Dijk',203,'0634567890','sofie@example.com',1,'VIP lid'),
('Tim','','Bakker',204,'0645678901','tim@example.com',1,NULL),
('Nina','','Jansen',205,'0656789012','nina@example.com',1,NULL);



/*
  Auteur       : aYSSAR
  Datum        : 2026-02-15
  Beschrijving : Tabel voor de lessen aangemaakt. Deze tabel bevat informatie over de groepslessen die worden aangeboden door de sportschool, zoals de naam van de les, prijs, datum, tijd, minimum en maximum aantal personen, beschikbaarheid en of de les actief is.
  Opmerkingen  : Valideren van minder van min en maximalen lessen word gedaan in php
*/


CREATE TABLE les (
    Id INT AUTO_INCREMENT PRIMARY KEY
    ,Naam VARCHAR(50) NOT NULL
    ,Prijs DECIMAL(5,2) NOT NULL
    ,Datum DATE NOT NULL
    ,Tijd TIME NOT NULL
    ,MinAantalPersonen TINYINT NOT NULL DEFAULT 3
    ,MaxAantalPersonen TINYINT NOT NULL DEFAULT 9
    ,Beschikbaarheid ENUM('Ingepland','Niet gestart','Gestart','Geannuleerd')
    ,IsActief TINYINT(1) NOT NULL DEFAULT 1
    ,Opmerking VARCHAR(250) NULL
    ,Datumaangemaakt DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
    ,Datumgewijzigd DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
)ENGINE=InnoDB;



INSERT INTO les
(Naam,Prijs,Datum,Tijd,MinAantalPersonen,MaxAantalPersonen,Beschikbaarheid,IsActief,Opmerking)
VALUES
('Yoga',12.50,'2026-03-01','09:00:00',3,9,'Ingepland',1,NULL),
('Pilates',15.00,'2026-03-02','11:00:00',3,9,'Ingepland',1,NULL),
('Spinning',10.00,'2026-03-03','18:00:00',3,9,'Niet gestart',1,NULL),
('Zumba',8.50,'2026-03-04','19:00:00',3,9,'Ingepland',1,NULL),
('BodyPump',14.00,'2026-03-05','17:00:00',3,9,'Geannuleerd',1,NULL);





/*
  Auteur       : aYSSAR
  Datum        : 2026-02-15
  Beschrijving : Tabel voor de reserveringen aangemaakt. Deze tabel bevat informatie over de reserveringen die leden maken voor groepslessen, zoals de naam van de reservering, datum, tijd, status van de reservering en of deze actief is.
  Opmerkingen  : Eventuele extra info of opmerkingen
*/


CREATE TABLE reservering (
    Id INT AUTO_INCREMENT PRIMARY KEY
    ,Voornaam VARCHAR(50) NOT NULL
    ,Tussenvoegsel VARCHAR(10)
    ,Achternaam VARCHAR(50) NOT NULL
    ,Nummer MEDIUMINT NOT NULL
    ,Datum DATE NOT NULL
    ,Tijd TIME NOT NULL
    ,Reserveringstatus VARCHAR(20) NOT NULL
    ,IsActief TINYINT(1) NOT NULL DEFAULT 1
    ,Opmerking VARCHAR(250) NULL
    ,Datumaangemaakt DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)
    ,Datumgewijzigd DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6)
)ENGINE=InnoDB;


INSERT INTO reservering
(Voornaam,Tussenvoegsel,Achternaam,Nummer,Datum,Tijd,Reserveringstatus,IsActief,Opmerking)
VALUES
('Laura','','Klein',201,'2026-03-01','09:00:00','Gereserveerd',1,NULL),
('Peter','','Wit',202,'2026-03-02','11:00:00','Gereserveerd',1,NULL),
('Sofie','','Dijkstra',203,'2026-03-03','18:00:00','Gereserveerd',1,NULL),
('Tim','','Bakker',204,'2026-03-04','19:00:00','Vrij',1,NULL),
('Nina','','Jansen',205,'2026-03-05','17:00:00','Gereserveerd',1,NULL);




select * from les;

SET sql_notes = 1;