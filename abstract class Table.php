<?php
require_once("interface Reservable.php");
abstract class Table implements Reservable
{
	protected $reservations = [];
	protected $personsCount; // i.e.: max. seats
	protected $tableNumber;

	public function __construct($tableNumber, $personsCount)
	{
		$this->personsCount = $personsCount;
		$this->tableNumber = $tableNumber;
	}

	/**
	 * @return integer
	 */
	public function getPersonsCount()
	{
		return $this->personsCount;
	}

	/**
	 * @return array
	 */
	public function getReservations() //wordt gebruikt in BookManager::cancelReservation(Reservation $reservation) methode
	{
		return $this->reservations;
	}

	/**
	 * @return integer
	 */
	public function getTableNumber()
	{
		return $this->tableNumber;
	}

	public function __toString()
	{
		$resultString = "Table number: $this->tableNumber\n";
		foreach ($this->reservations as $reservation) {
			$resultString .= $reservation->__toString() . PHP_EOL;
		}
		return $resultString;
	}

	public function addReservation(Reservation $reservation)
	{
		// $this->checkForValidReservation($reservation);
		$this->reservations[] = $reservation; //$table->checkForValidReservation($reservation); staat nu (ook) in class BookManager::bookTable($reservation)
	}

	public function removeReservation(/*Reservation $reservation /* weggecomment omdat de $key nu 'global' vanuit de BookManager Class wordt gehaald*/)
	{
		// if (($key = array_search($reservation, $this->reservations)) !== false) {//dit stukje code is nu (versie 2.0) ook (deels) weggehaald uit de BookManager, want de nieuwe BookManager::cancelReservation($reservation)-methode gebruikt voor ALLE te verwijderen reserveringen de $id (van de reservering) bij het vaststellen van de $key(s) (zie: cancelReservation(Reservation $reservation)).
		global $key; //toegevoegd omdat bovenstaande stukje code (met de $key definitie) is verplaatst //(en inmiddels verder aangepast, zie comment hierboven)
		unset($this->reservations[$key]);
	}

	public function checkForValidReservation(Reservation $reservation)
	{
		global $countReservations; // geinitialiseerd op 0 in class BookManager::bookTable($reservation) {}
		global $check;
		$check = False; // if ($check == True) {$table->addReservation($reservation);} eveneens in class BookManager::bookTable()

		foreach ($this->reservations as $existingReservation) {
			if (
				($reservation->getStartDate() >= $existingReservation->getStartDate() &&
					$reservation->getStartDate() <= $existingReservation->getEndDate())
			) {
				$countReservations += 1; //als alle tafels bezet zijn is $countReservations gelijk aan het aantal tafels in de array
				$check = True;
			} elseif (
				$reservation->getEndDate() >= $existingReservation->getStartDate() &&
				$reservation->getEndDate() <= $existingReservation->getEndDate()
			) {
				$countReservations += 1; //als alle tafels bezet zijn is $countReservations gelijk aan het aantal tafels in de array
				$check = True;
			}
		}
		global $numberCorresponds; //more or less...(reservering kan 1 persoon minder zijn dan tafel max. aan kan)
		// global $toomuch; //LET OP: niet meer nodig want in BookManager::countSplits() wordt deze situatie als volgt afgevangen: $table->getPersonsCount() == $x && $rest > $x (waarbij $rest = $reservation->getNumberOfGuests() die bij elke iteratie van de tafels-loop wordt afgeteld tot de $splitCount bekend is (zie BookManager::countSplits()-methode); het lijkt of het zo ingewikkelder is geworden, maar de betreffende constructie was nodig om ZOWEL reserveringen > 6 personen ALS splitReservations in het algemeen eenvoudiger (of consequenter) af te handelen)
		$numberCorresponds = True; //wordt op False gezet als de reservering groter is dan het maximum dat de tafel aan kan, of kleiner dan het maximum minus 1.
		// $toomuch = False; //wordt op True gezet als de reservering groter is dan het maximum dat de tafel aan kan

		if ($reservation->getNumberOfGuests() > $this->getPersonsCount()) {
			$numberCorresponds = False;
			// $toomuch = True; //te veel gasten voor de tafel
		} else if ($reservation->getNumberOfGuests() < -1 + $this->getPersonsCount()) { //oftewel: als bijv. 2 personen een 4 persoonstafel reserveren, kan dat niet (zolang er nog 2 persoonstafels beschikbaar zijn, is althans de bedoeling); 3 personen kunnen wel een 4 persoonstafel reserveren (-1 + 4)
			$numberCorresponds = False;
			// $toomuch = False; //te weinig gasten voor de tafel
		}
	}
}
