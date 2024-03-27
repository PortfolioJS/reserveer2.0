<?php
class BookManager
{
	public static function countReservedTables(Reservation $reservation)
	{
		global $tables;
		global $check; //zie Table::checkForValidReservation(): $check = True als tafel op tijdstip is gereserveerd
		global $countReservations; //telt reserveringen (eigenlijk: gereserveerde tafels) op bepaald tijdstip (zie: Table::checkForValidReservation()
		$countReservations = 0;
		global $countReservedTablesForSix;
		global $countReservedTablesForFour;
		global $countReservedTablesForTwo;
		$countReservedTablesForSix = 0;
		$countReservedTablesForFour = 0;
		$countReservedTablesForTwo = 0;
		global $seatsAvailable;

		foreach ($tables as $table) {

			$table->checkForValidReservation($reservation);
			if ($check == True && $table->getPersonsCount() == 6) {
				$countReservedTablesForSix += 1;
			} else if ($check == True && $table->getPersonsCount() == 4) {
				$countReservedTablesForFour += 1;
			} else if ($check == True && $table->getPersonsCount() == 2) {
				$countReservedTablesForTwo += 1;
			}
		}


		$seatsAvailable = ((TableForSix::$count - $countReservedTablesForSix) * 6) + ((TableForFour::$count - $countReservedTablesForFour) * 4) + ((TableForTwo::$count - $countReservedTablesForTwo) * 2);

		//onderstaande TEST echo's laten het aantal gereserveerde tafels per tijdstip van de reservering zien, voorafgaand aan het (eventueel) boeken van de tafel (kan dus veranderen per reservering, is vooral overzichtelijk om te testen of systeem naar behoren werkt op zelfde overlappende data/tijdstippen)
		echo /*"Date/time: " . $reservation->getStartDate()->format("d-m-y ") . $reservation->getStartDate()->format("H:i ") . */ "Table for Six (reserved/total): " . $countReservedTablesForSix . "/" . TableForSix::$count . "<br>";
		echo /*"Date/time: " . $reservation->getStartDate()->format("d-m-y ") . $reservation->getStartDate()->format("H:i ") . */ "Table for Four (reserved/total): " . $countReservedTablesForFour . "/" . TableForFour::$count . "<br>";
		echo /*"Date/time: " . $reservation->getStartDate()->format("d-m-y ") . $reservation->getStartDate()->format("H:i ") . */ "Table for Two (reserved/total): " . $countReservedTablesForTwo . "/" . TableForTwo::$count . "<br>";
	}


	public static function countSplits(Reservation $reservation) //ingevoegd om de $splitCount te berekenen/instellen
	{
		global $tables;
		global $check; //zie Table::checkForValidReservation(): $check = True als tafel op tijdstip is gereserveerd
		global $seatsAvailable;
		$rest = $reservation->getNumberOfGuests(); //is nodig voor het tellen van het aantal tafels waarover een reservering verdeeld moet worden ($splitCount) en wordt hieronder afgeteld met het aantal personen dat een tafel max. aankan
		global $splitCount; //deze global is dus nodig om de protected $splitCount van de nieuw te creëren reserveringsobjecten in te stellen (bij reservering opgesplitst over meerdere tafels)
		$splitCount = 1; //bij standaardreservering van 1 tafel staat $splitCount DEFAULT op 1 (zie class Reservation) EN bij de andere opgesplitste reserveringen wordt de $splitCount bij het creëren van de nieuwe reserveringsobjecten 'ingesteld' - zie hieronder bij Bookmanager::bookTable()

		foreach ($tables as $table) {
			$table->checkForValidReservation($reservation); {

				if ($seatsAvailable >= $reservation->getNumberOfGuests()) { // hier geen $rest, omdat die wordt afgeteld (om de splitCount te tellen/berekenen)

					if ($check == False && $table->getPersonsCount() == 6 && $rest > 6) {
						$splitCount += 1;
						$rest -= 6;
					} else if ($check == False && $table->getPersonsCount() == 6 && $rest > 4 && $rest <= 6) { //zodra de $splitCount bekend is:
						break;
					} else if ($check == False && $table->getPersonsCount() == 4 && $rest > 4) {
						$splitCount += 1;
						$rest -= 4;
					} else if ($check == False && $table->getPersonsCount() == 4 && $rest > 2 && $rest <= 4) { //zodra de $splitCount bekend is:
						break;
					} else if ($check == False && $table->getPersonsCount() == 2 && $rest > 2) {
						$splitCount += 1;
						$rest -= 2;
					} else if ($rest <= 2) { //zodra de $splitCount bekend is:
						break;
					}
				} else {
					break;
				}
			}
		}
	}

	public static function bookTable(Reservation $reservation)
	{
		global $tables;
		global $check;
		global $countReservations; //telt reserveringen (eigenlijk: gereserveerde tafels) op bepaald tijdstip (zie: Table::checkForValidReservation()
		$countReservations = 0;
		global $numberCorresponds;
		global $toomuch; //is niet meer nodig, want BookManager::countSplits() heeft deze functionaliteit min of meer overgenomen (zie ook comment Table::checkForValidReservation($reservation), waar $toomuch eerst werd vastgesteld)
		$onetwo = 0; //TESTVARIABELE
		$numberCorresponds = True;
		global $countReservedTablesForSix;
		global $countReservedTablesForFour;
		global $countReservedTablesForTwo;
		global $seatsAvailable;
		$rest = $reservation->getNumberOfGuests();
		global $splitCount; // zie BookManager::countReservedTables() (hierboven)
		$firstSplit = False; // nodig voor de eerste reservering (voor de 'split') bij SplitReservations

		foreach ($tables as $table) {
			$onetwo += 1; //TESTVARIABELE
			$table->checkForValidReservation($reservation); //bij 'invalid' reservation: $countReservations +=1
			//als $countReservations gelijk is aan het aantal tafels in de array zijn alle tafels gereserveerd op bepaald tijdstip
			if ($countReservations == count($tables)) {
				Reservation::$count--; // als reserveren niet mogelijk is, wordt de reservering niet geteld (BELANGRIJK: omdat het aanmaken van de Reservation $id gekoppeld is aan de $count)
				echo "Helaas zijn al onze tafels gereserveerd op " . $reservation->getStartDate()->format("d-m-y") . " om " . $reservation->getStartDate()->format("H:i") . " uur.";
			} else 
			if (($reservation->getNumberOfGuests() > $seatsAvailable) && $seatsAvailable > 0) { //als $seatsAvailable == 0: dan wil je dat alle gereserveerde tafels worden geteld, zodat (zie hierboven): 
				Reservation::$count--; // als reserveren niet mogelijk is, wordt de reservering niet geteld (BELANGRIJK: omdat het aanmaken van de Reservation $id gekoppeld is aan de $count)
				echo "OP " . $reservation->getStartDate()->format("d-m-y") . " om " . $reservation->getStartDate()->format("H:i") . " uur ONVOLDOENDE PLAATSEN BESCHIKBAAR voor " . $reservation->getNumberOfGuests() . " personen.";
				break;
			} else if ($check == False && $splitCount > 1) {
				$SplitReservation = new Reservation($reservation->getStartDate()->format("y-m-d H:i"), $reservation->getGuest(), $reservation->getNumberOfGuests(), True, $reservation->getID(), $splitCount); //de laatste drie argumenten staan in de $splitReservation array
				if ($rest >= -1 + $table->getPersonsCount()) { //zodat bij de iteraties elke keer de tafel wordt gereserveerd met het meeste aantal personen
					if ($firstSplit == True) {
						$table->addReservation($SplitReservation);
					} else {
						$table->addReservation($reservation); //ook bij Splitreservation moet de oorspronkelijke reservering bij de eerste iteratie bewaard blijven, o.a. omdat die nodig is bij verwijderen reserveringen (nu dus niet meer omdat de nieuwe cancelReservation(Reservation $reservation)-methode de (verwijderings)$key vaststelt aan de hand van de $id van de reservering)...
						$reservation->setSplitReservation(True); //... maar, ook de $splitReservation bool|array kan sowieso lastig als array ingevuld worden voor de EERSTE reservering (wanneer het nog een bool is, immers de array wordt pas gevuld bij het creëren van een nieuw $splitreservation object)
						$reservation->setSplitCount($splitCount);
						$firstSplit = True;
					}
					echo "Tafel " . $table->getTableNumber() . " voor " . $table->getPersonsCount() . " personen gereserveerd voor "
						. $reservation->getGuest()->getFullName()
						. " op " . $reservation->getStartDate()->format("d-m-y") . " om " . $reservation->getStartDate()->format("H:i") . " uur. (reservation-id: " . $reservation->getID() . "/ aantal tafels: " . $reservation->getSplitCount() . ")<br>";
					if ($table->getPersonsCount() == 6) {
						$countReservedTablesForSix += 1; //omdat de Bookmanager::countReservedTables() methode niet nog eens wordt uitgevoerd, terwijl de $countR(etc.) in deze bookTable() methode bij het loopen over de $tables array nodig is mbt if/then
					} else if ($table->getPersonsCount() == 4) {
						$countReservedTablesForFour += 1; //idem (zie hierboven)
					} else if ($table->getPersonsCount() == 2) {
						$countReservedTablesForTwo += 1; //idem (zie hierboven)
					}
					$rest = $rest - $table->getPersonsCount(); //zodat bij elke iteratie het restgetal kleiner wordt navenant aan de gereserveerde tafel
					if ($rest == 0) {
						break;
					}
				} else if ($countReservedTablesForTwo === TableForTwo::$count && $rest > 0 && $rest <= 2) { //ALS alle 2-persoonstafels gereserveerd zijn EN $rest = 1 of 2 (gaat de if niet meer op): pak DAN de tafel die voorhanden is
					// if ($firstSplit == True) {//waarschijnlijk niet nodig, omdat hij hier pas komt op het moment dat de $firstSplit een feit is EN het laatste $restje een plek zoekt
					$table->addReservation($SplitReservation);
					echo "Tafel " . $table->getTableNumber() . " voor " . $table->getPersonsCount() . " personen gereserveerd voor "
						. $reservation->getGuest()->getFullName()
						. " op " . $reservation->getStartDate()->format("d-m-y") . " om " . $reservation->getStartDate()->format("H:i") . " uur. (reservation-id: " . $reservation->getID() . "/ aantal tafels: " . $reservation->getSplitCount() . ")<br>";
					if ($table->getPersonsCount() == 6) {
						$countReservedTablesForSix += 1; //omdat de Bookmanager::countReservedTables() methode niet nog eens wordt uitgevoerd, terwijl de $countR(etc.) in deze bookTable() methode bij het loopen over de $tables array nodig is mbt if/then
					} else if ($table->getPersonsCount() == 4) {
						$countReservedTablesForFour += 1; //idem (zie hierboven)
						// } else if ($table->getPersonsCount() == 2) { // niet nodig: $countReservedTablesForTwo === TableForTwo::$count
						// 	$countReservedTablesForTwo += 1;
					}
					$rest = $rest - $rest; //het laatste $restje kan weg (is waarschijnlijk ook niet nodig: de break alleen was genoeg geweest)
					break;
				}
			} else if ($check == False && $numberCorresponds == True) {
				$table->addReservation($reservation);
				echo "Tafel " . $table->getTableNumber() . " voor " . $table->getPersonsCount() . " personen gereserveerd voor "
					. $reservation->getGuest()->getFullName()
					. " op " . $reservation->getStartDate()->format("d-m-y") . " om " . $reservation->getStartDate()->format("H:i") . " uur.";
				break;
			} else if ($check == False && $numberCorresponds == False && $toomuch == True) { //dit scenario doet zich bijv. voor wanneer alle tafels voor 6 zijn gereserveerd, maar er nog wel tafels voor 4 en/of 2 beschikbaar zijn.
				// 	// if ($reservation->getNumberOfGuests() > 6) {
				// 	// 	//LET OP: reserveringen boven de 6 personen worden niet doorgezet, maar krijgen onderstaande melding REDEN: padafkhankelijkheid/incrementalisme (voortbordurend op eerder gemaakte keuzes is de 'technical debt' wat te hoog opgelopen om dit reserveersysteempje eenvoudig aan te passen - het zou eigenlijk opnieuw doordacht moeten worden maar mijn 'budget' is op...)
				// 	// 	Reservation::$count--; // als reserveren niet mogelijk is, wordt de reservering niet geteld (BELANGRIJK: omdat het aanmaken van de Reservation $id gekoppeld is aan de $count)
				// 	// 	echo "Voor groepsreserveringen: neemt u alstublieft telefonisch contact met ons op voor de mogelijkheden.";
				// 	// 	break;
				// 	// } else 
				//LET OP: nu reserveringen van > 6 alsnog kunnen worden afgehandeld, is een groot deel van wat hieronder staat ook overbodig geworden (althans: na nog wat additionele aanpassingen in voornamelijk de BookManager::Count(etc.) methodes).
				// 	if ($countReservedTablesForSix < TableForSix::$count && $countReservedTablesForTwo < -1 + TableForTwo::$count) { //implicatie: reservering van 4 of 3 (anders was de 6 persoonstafel in de array eerst wel gepakt), terwijl alle 4 persoonstafels al bezet zijn (want toomuch = True); in dat geval checken of er nog minimaal 2 x 2 persoonstafel vrij zijn

				// 		$x = $reservation->getNumberOfGuests() - 2;
				// 		$rest = $reservation->getNumberOfGuests() - $x; // LET OP: rest = 2 (zie hierboven, momenteel werkt onderstaande niet bij: 3 x 2 persoonstafel en ook niet bij: 2 x 4 persoonstafel; alleen combinaties van 4 + 2 of 2 + 2 persoonstafel zijn mogelijk).
				// 		if ($x == $table->getPersonsCount() or $x == -1 + $table->getPersonsCount()) {
				// 			$table->addReservation($reservation);
				// 			$reservation->setSplitReservation(True);
				// 			$reservation->setSplitCount(2); // de $splitCount van de eerste reservering (default is immers 1) wordt gelijkgezet aan die van de $SplitReservation (in de $splitReservation array: index 2, hieronder)
				// 			echo "Tafel " . $table->getTableNumber() . " voor " . $table->getPersonsCount() . " personen gereserveerd voor "
				// 				. $reservation->getGuest()->getFullName()
				// 				. " op " . $reservation->getStartDate()->format("d-m-y") . " om " . $reservation->getStartDate()->format("H:i") . " uur. PLUS: <br>";
				// 			//onderstaande reservering zou worden overschreven wanneer er nog een soortgelijke reservering voor dezelfde of andere datum/tijd zou binnenkomen (daarom deze reserveringsvariabele niet gebruiken in de array(s) met reserveringen van de tafels op de index.php, maar de bestaande reservering verdubbelen met aanpassing van het aantal personen - dus minus het aantal personen van de eerste tafel) IDEM voor de andere $SplitReservations
				// 			$SplitReservation = new Reservation($reservation->getStartDate()->format("y-m-d H:i"), $reservation->getGuest(), $rest, True, $reservation->getID(), 2); //de laatste drie argumenten staan in de $splitReservation array

				// 			BookManager::bookTable($SplitReservation);
				// 			echo " (Wees gerust, we zetten de tafels heus speciaal voor jullie groepje van " . $reservation->getNumberOfGuests() . " bij elkaar...)";
				// 			break;
				// 		}
				// 	} else if ( /*$countReservedTablesForSix == TableForSix::$count &&/*voorgaande wordt al geimpliceerd*/$countReservedTablesForFour < TableForFour::$count && $countReservedTablesForTwo < TableForTwo::$count) { //oftewel: als de 6 persoonstafels gereserveerd zijn, maar er nog wel voldoende tafels van 4 en 2 beschikbaar zijn: splits de reservering

				// 		$x = $reservation->getNumberOfGuests() - 2;
				// 		$rest = $reservation->getNumberOfGuests() - $x; // LET OP: rest = 2 (zie hierboven, momenteel werkt deze methode nog niet bij: 2 x 4 persoonstafel; alleen combinaties van 4 + 2 of 2 + 2 persoonstafel zijn hier mogelijk - en hieronder staat een combinatie van 3 x 2 persoonstafels, een reservation split in driëen).
				// 		if ($x == $table->getPersonsCount() or $x == -1 + $table->getPersonsCount()) {
				// 			$table->addReservation($reservation);
				// 			$reservation->setSplitReservation(True);
				// 			$reservation->setSplitCount(2); // de $splitCount van de eerste reservering (default is immers 1) wordt gelijkgezet aan die van de $SplitReservation (in de $splitReservation array: index 2, hieronder)
				// 			echo "Tafel " . $table->getTableNumber() . " voor " . $table->getPersonsCount() . " personen gereserveerd voor "
				// 				. $reservation->getGuest()->getFullName()
				// 				. " op " . $reservation->getStartDate()->format("d-m-y") . " om " . $reservation->getStartDate()->format("H:i") . " uur. PLUS: <br>";
				// 			$SplitReservation = new Reservation($reservation->getStartDate()->format("y-m-d H:i"), $reservation->getGuest(), $rest, True, $reservation->getID(), 2); //de laatste drie argumenten staan in de $splitReservation array
				// 			BookManager::bookTable($SplitReservation);
				// 			echo " (Wees gerust, we zetten de tafels heus speciaal voor jullie groepje van " . $reservation->getNumberOfGuests() . " bij elkaar...)";
				// 			break;
				// 		}
				// 	} else if ( /*$countReservedTablesForSix == TableForSix::$count &&/*voorgaande wordt al geimpliceerd*/$countReservedTablesForTwo < -1 + TableForTwo::$count && $reservation->getNumberOfGuests() == 4 | $reservation->getNumberOfGuests() == 3) { //OOK reservering van 4 of 3, terwijl alle 4 persoonstafels al bezet zijn (want toomuch = True) MAAR: de 6 persoonstafels zijn nu allemaal gereserveerd; in dat geval nogmaals checken of er nog minimaal 2 x 2 persoonstafel vrij zijn

				// 		$x = $reservation->getNumberOfGuests() - 2;
				// 		$rest = $reservation->getNumberOfGuests() - $x; // LET OP: rest = 2 (zie hierboven, momenteel werkt onderstaande niet bij: 3 x 2 persoonstafel en ook niet bij: 2 x 4 persoonstafel; alleen combinaties van 4 + 2 of 2 + 2 persoonstafel zijn mogelijk).
				// 		if ($x == $table->getPersonsCount() or $x == -1 + $table->getPersonsCount()) {
				// 			$table->addReservation($reservation);
				// 			$reservation->setSplitReservation(True);
				// 			$reservation->setSplitCount(2); // de $splitCount van de eerste reservering (default is immers 1) wordt gelijkgezet aan die van de $SplitReservation (in de $splitReservation array: index 2, hieronder)
				// 			echo "Tafel " . $table->getTableNumber() . " voor " . $table->getPersonsCount() . " personen gereserveerd voor "
				// 				. $reservation->getGuest()->getFullName()
				// 				. " op " . $reservation->getStartDate()->format("d-m-y") . " om " . $reservation->getStartDate()->format("H:i") . " uur. PLUS: <br>";
				// 			$SplitReservation = new Reservation($reservation->getStartDate()->format("y-m-d H:i"), $reservation->getGuest(), $rest, True, $reservation->getID(), 2); //de laatste drie argumenten staan in de $splitReservation array

				// 			BookManager::bookTable($SplitReservation);
				// 			echo " (Wees gerust, we zetten de tafels heus speciaal voor jullie groepje van " . $reservation->getNumberOfGuests() . " bij elkaar...)";
				// 			break;
				// 		}
				// 	} else if ( /*$countReservedTablesForFour == TableForFour::$count && /*voorgaande wordt al geimpliceerd*/$countReservedTablesForTwo < -2 + TableForTwo::$count && $reservation->getNumberOfGuests() == 6 | $reservation->getNumberOfGuests() == 5) { //oftewel: als de 6 en de 4 persoonstafels gereserveerd zijn, maar er nog wel voldoende tafels van 2 beschikbaar zijn: splits de reservering in drieën

				// 		$x = $reservation->getNumberOfGuests() - 4; //oftewel: $x = 1 of 2
				// 		$rest1 = $reservation->getNumberOfGuests() - $x - 2; // oftewel: $rest1 = 2 (zie bovenstaande comment bij LET OP)
				// 		$rest2 = $reservation->getNumberOfGuests() - $x - 2; // oftewel: $rest2 = 2 (zie bovenstaande comment bij LET OP)
				// 		if ($x == $table->getPersonsCount() or $x == -1 + $table->getPersonsCount()) { //oftewel: een 2 persoonstafel wordt gereserveerd
				// 			$table->addReservation($reservation);
				// 			$reservation->setSplitReservation(True);
				// 			$reservation->setSplitCount(3); // de $splitCount van de eerste reservering (default is immers 1) wordt gelijkgezet aan die van de $SplitReservation (in de $splitReservation array: index 2, hieronder)
				// 			echo "Tafel " . $table->getTableNumber() . " voor " . $table->getPersonsCount() . " personen gereserveerd voor "
				// 				. $reservation->getGuest()->getFullName()
				// 				. " op " . $reservation->getStartDate()->format("d-m-y") . " om " . $reservation->getStartDate()->format("H:i") . " uur. PLUS2: <br>";
				// 			$SplitReservation = new Reservation($reservation->getStartDate()->format("y-m-d H:i"), $reservation->getGuest(), $rest1, True, $reservation->getID(), 3); //de laatste drie argumenten staan in de $splitReservation array
				// 			BookManager::bookTable($SplitReservation); //na boeken tafel kan $SplitReservation overschreven worden met nieuw reserveringsobject (zie hieronder)
				// 			echo " (Wees gerust, we zetten de tafels heus speciaal voor jullie groepje van " . $reservation->getNumberOfGuests() . " bij elkaar...)<br>";
				// 			$SplitReservation = new Reservation($reservation->getStartDate()->format("y-m-d H:i"), $reservation->getGuest(), $rest2, True, $reservation->getID(), 3); //de laatste drie argumenten staan in de $splitReservation array
				// 			BookManager::bookTable($SplitReservation);
				// 			echo " (Wees gerust, we zetten de tafels heus speciaal voor jullie groepje van " . $reservation->getNumberOfGuests() . " bij elkaar...)";
				// 			break;
				// 		}
				// 	}
				// DEZE echo STAAT HIER VOLLEDIG NUTTELOOS ONDER EEN BREAK (de break boven het eerder weggecommente deel) EN is sowieso NIET NODIG nu reserveringen ALLEMAAL worden opgesplitst):
				// echo "Helaas zijn er geen tafels voor " . $reservation->getNumberOfGuests() . " personen meer beschikbaar op dit tijdstip.";
				// break;
			} else if ($check == False && $numberCorresponds == False && $toomuch == False) { //is niet meer nodig (zie o.a. comment Table::checkForValidReservation($reservation) )*/) { //bijv. reservering voor 1 of 2 personen van een 4 of 6 persoonstafel of reservering voor 3/4 personen van een 6 persoonstafel (en uiteindelijk ook reservering 1/2 personen van 6-persoonstafel, zie comment hieronder bij LET OP)
				if ($countReservedTablesForFour < TableForFour::$count && $countReservedTablesForTwo == TableForTwo::$count && $reservation->getNumberOfGuests() >= -3 + $table->getPersonsCount()) { //oftewel: als alle 2 persoonstafels gereserveerd zijn, pak dan een 4 persoonstafel (ook in geval 1 persoonsreservering)
					$table->addReservation($reservation);
					echo "Tafel " . $table->getTableNumber() . " voor " . $table->getPersonsCount() . " personen gereserveerd voor "
						. $reservation->getGuest()->getFullName()
						. " op " . $reservation->getStartDate()->format("d-m-y") . " om " . $reservation->getStartDate()->format("H:i") . " uur.";
					break;
				} else if ($countReservedTablesForSix < TableForSix::$count && $countReservedTablesForFour == TableForFour::$count && $countReservedTablesForTwo >= -1 + TableForTwo::$count) { //oftewel: als alle 4 en 2 persoonstafels (op evt 1 na) zijn gereserveerd, maar er zijn nog wel 6 persoonstafels vrij: reserveer in dit uiterste geval toch een 6 persoonstafel voor 3 of 4 personen. LET OP: deze ELSE IF kan op zichzelf GEEN tafel voor 6 personen reserveren bij een reservering voor 2 personen, maar WEL in combinatie met bovenstaande IF (als zowel alle 2 als 4 persoonstafels zijn gereserveerd: pak dan een 6 persoonstafel (ook in geval 1 persoonsreservering))
					$table->addReservation($reservation);
					echo "Tafel " . $table->getTableNumber() . " voor " . $table->getPersonsCount() . " personen gereserveerd voor "
						. $reservation->getGuest()->getFullName()
						. " op " . $reservation->getStartDate()->format("d-m-y") . " om " . $reservation->getStartDate()->format("H:i") . " uur.";
					break;
				} else {
					echo $onetwo . ". (TEST: Too few persons for this table.)<br>";
				}
			} else if ($check == True) {
				echo $onetwo . ". (TEST: Table reserved.)<br>";
			}
		}
	}

	// LET OP: Onderstaande cancelTable(Reservation $reservation) is VERVANGEN door de cancelTableS($id) daaronder te VERANDEREN in cancelReservation($reservation) die zowel enkelvoudige reserveringen als SplitReservations kan verwijderen op basis van het $id van de reservering
	// public static function cancelTable(Reservation $reservation)
	// {
	// 	global $tables;
	// 	global $key; //wordt indien nodig (d.w.z. bij een SplitReservation) overschreven in de cancelTables($id) functie (zie hieronder)
	// 	global $id;

	// 	foreach ($tables as $table) {

	// 		if (($key = array_search($reservation, $table->getReservations())) !== false && empty($reservation->getSplitReservation())) {
	// 			$table->removeReservation();
	// 			Reservation::$count--; //de reservering wordt afgeteld (BELANGRIJK: omdat het aanmaken van de Reservation $id gekoppeld is aan de $count)
	// 			break; //break kan hier omdat er maar 1 reservering te verwijderen is
	// 		} elseif (($key = array_search($reservation, $table->getReservations())) !== false && !empty($reservation->getSplitReservation())) {
	// 			// if ($reservation->getSplitReservation() === True) { //deze if is strikt genomen niet nodig, omdat: als $splitReservation !empty is, hij True is (zie class Reservation: __construct())
	// 			$id = $reservation->getID(); //$id is bij SplitReservation nodig om ook de tweede (en evt. derde) reservering te verwijderen
	// 			Bookmanager::cancelTableS($id); //hiermee worden alle SplitReservations verwijderd (zie functie hieronder)
	// 			Reservation::$count--; //de SplitReservation wordt 1 keer afgeteld (BELANGRIJK: omdat het aanmaken van de Reservation $id gekoppeld is aan de $count)
	// 			break; //want deze loop/functie heeft zijn taak gedaan (de SplitReservations worden nu op basis van hun $id verwijderd)
	// 			// }
	// 		}
	// 	}
	// }

	public static function cancelReservation(Reservation $reservation) //op basis van het $id van de reservering worden de SplitReservations eruitgehaald
	{
		global $tables;
		global $key; //wordt hieronder onderschreven
		// global $id; //de $id van de reservering is in de cancelTable() vastgesteld (zie hierboven) //LET OP: nu dus niet meer (zie hieronder...)
		global $count;
		$count = 0;
		$id = $reservation->getID(); //... want de $id van de reservering wordt nu hier (local) vastgesteld (zie ook comment bij de verwijderde cancelTable(Reservation $reservation))
		$break = False;

		foreach ($tables as $table) {
			foreach ($table->getReservations() as $reservation) {
				if ($reservation->getID() === $id) {
					$key = array_search($reservation, $table->getReservations());
					$table->removeReservation();
					$count += 1;
					if ($count === $reservation->getSplitCount()) { // ALS alle SplitReservations van de betreffende reservering zijn verwijderd...
						$break = True;
						break; // ... DAN: de foreach $reservations loop van de tafel wordt gebreakt EN ...
					}
				}
			}
			if ($break === True) {
				break; // ... de foreach $tables loop wordt OOK gebreakt
			}
		}
		Reservation::$count--; //de gecancelde reservering wordt afgeteld (BELANGRIJK: omdat het aanmaken van de Reservation $id gekoppeld is aan de $count)
	}
}
