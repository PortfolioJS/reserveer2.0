# reserveer2.0
Extension/improvement of restaurant reservation system Reserveer(1.0)

The first version of this reservation system (Reserveer) couldn't handle reservations of more than six guests. To change that, I added an extra method in the BookManager Class (BookManager::countSplit($reservation)), which loops over the tables to check which tables are free to reserve (basically the same functionality as te BookManager::countReservations($reservation) method). But what it really does, is checking whether a reservation needs to be split (that is: more tables need to be reserved), and if so, then it calculates over how many tables the reservation needs to be divided. (In the index page there are references to this new method too.)
This functionality not only applies to reservations of more than six guests, but also for smaller reservations that need to be split down. So, some of the code in the BookManager::bookTable($reservation) method, which already splits reservations in the original reservation system (Reserveer 1.0), could now be deleted (or commented away). Because this latter code is now removed, the $splitCount and the $numberOfGuests of every reserved table are now equally set for all $splitReservations; and another little change could be made in the Abstract Class Table: in the Table::checkForValidReservations($reservation) method, the $toomuch variable, which checks whether the reservations number of guests is too big for the table in question to handle, could now be commented away. (Also, in this and other classes, I removed mentions/comments about the original example on GitHub, which inspired this reservation system in the first place, you can find these in the Reserveer(1.0) reservation system.)
One last little change: looking at this code again, after quite a while, I realised that my solution for cancelling reservations in the BookManager Class, could be made simpler. Inspired by the GitHub example mentioned earlier, I initially used the $reservation (which is an object) as $key to be deleted from the array of reservations of the Table objects (in the BookManager::cancelTable($reservation). This works fine for reservations of one table, but my solution of splitting reservations over more tables implies creating more Reservation objects. So, my earlier solution for cancelling reservations of more than one table was to add an extra BookManager::cancelTableS($reservation), which takes care for the other reservation objects, based on their $id as $key - this other $reservation objects already had the same $id. With a fresh look, it now dawned upon me, one single BookManager::cancelReservation($reservation) method could cancel all reserved tables in one swoop, if the $key for all reserved tables to be cancelled was based on the $id of the reservation.
