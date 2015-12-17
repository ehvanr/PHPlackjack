DROP DATABASE IF EXISTS Blackjack;
CREATE DATABASE Blackjack;
USE Blackjack;

DROP TABLE IF EXISTS `Games`;
DROP TABLE IF EXISTS `Users`;
DROP TABLE IF EXISTS `GameUsers`;
DROP TABLE IF EXISTS `ChatHistory`;
DROP TABLE IF EXISTS `Leaderboard`;

CREATE TABLE `Games` (
  `GameID` int(12) NOT NULL AUTO_INCREMENT,
  `GameName` varchar(255) NOT NULL,
  `DealerHand` varchar(255) DEFAULT NULL,
  `DeckPointer` int(12) DEFAULT NULL,
  `CurrentDeck` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`GameID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `Users` (
  `Username` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `GameUsers` (
  `GameID` int(12) NOT NULL,
  `Username` varchar(255) NOT NULL,
  `UserStatus` varchar(255) NOT NULL,
  `UserHand` varchar(255) NOT NULL,
  PRIMARY KEY (`GameID`,`Username`),
  KEY `Username` (`Username`),
  CONSTRAINT `gameusers_ibfk_1` FOREIGN KEY (`GameID`) REFERENCES `Games` (`GameID`),
  CONSTRAINT `gameusers_ibfk_2` FOREIGN KEY (`Username`) REFERENCES `Users` (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ChatHistory` (
  `MessageID` int(12) NOT NULL AUTO_INCREMENT,
  `Username` varchar(255) NOT NULL,
  `Message` varchar(255) NOT NULL,
  `Time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`MessageID`),
  KEY `Username` (`Username`),
  CONSTRAINT `chathistory_ibfk_1` FOREIGN KEY (`Username`) REFERENCES `Users` (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `Leaderboard` (
  `Username` varchar(255) NOT NULL,
  `HandsWon` int(12) NOT NULL DEFAULT '0',
  `HandsLost` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY `Username` (`Username`),
  KEY `Username` (`Username`),
  CONSTRAINT `leaderboard_ibfk_1` FOREIGN KEY (`Username`) REFERENCES `Users` (`Username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
