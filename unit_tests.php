<?php
require( 'Cards.php' ) ;
require( 'DB.php' ) ;
header( 'Content-type: text/plain' ) ;

// MySQL db to track hands in batches
$mysqli = new mysqli( $hostname, $username, $password, $db_name );
if (mysqli_connect_error()) {
    die('Connect Error (' . mysqli_connect_errno() . ') '
            . mysqli_connect_error());
}

print "\nDB connection successful... (" . $mysqli->host_info . ")\n\n";

/*
$card = new Card( CLUBS, ACE ) ;
var_dump( $card->get() ) ;
var_dump( $card->get_suit() ) ;
var_dump( $card->get_value() ) ;
var_dump( $card->get_as_string() ) ;
*/

print "Starting time and memory tracking...\n\n" ;
$start_mem = memory_get_usage() ;
$start = microtime( true ) ;

//$deck = new Deck() ;
//$deck->shuffle( 5 ) ;
//var_dump( $deck->get_all_cards() ) ;

//print "\nCards in a deck: " . count( $deck->get_all_cards() ) . "\n" ;

//$a_card = $deck->get_card() ;
//$a_card_str = $a_card->get_as_string() ;

//var_dump( $a_card ) ;
//print "\ncard is: {$a_card_str}\n" ;

//var_dump( $deck->get_card() ) ;
//var_dump( $deck->get_card() ) ;
//var_dump( $deck->get_card() ) ;

//print "\nCards in a deck: " . count( $deck->get_all_cards() ) ;
//$deck->shuffle( 10 ) ;


/*
$n = 0;
foreach( $deck->get_all_cards() as $card )
{
	$n++ ;
	print "{$n}. {$card->get_as_string()} has value of {$card->get_value()}\n" ;
}
*/

$bust_count = 0 ;
$blackjack_count = 0 ;
$held_count = 0 ;

$game_count = 100 ;
$actual_game_count = 0 ; 	// incremented via iteration
$deck_count = 6 ;
$shuffle_count = 8 ;
$num_players = 3 ; 			// # hands to deal (player count + dealer)
$num_cards_per_hand = 2 ; 	// # cards per hand (game-dependent)
$necessary_card_count = ( $num_players * $num_cards_per_hand ) + 52 ;	// modify this for single and double deck blackjack
	
print "Generating initial shoe of {$deck_count} decks shuffled {$shuffle_count} times...\n\n" ;
$deck = new MultiDeck( $deck_count, FALSE ) ;
$deck->shuffle( $shuffle_count ) ;

print "Processing {$game_count} games; {$deck_count} decks in the shoe...\n\n" ;
for( $game=0; $game<$game_count; $game+=1 )
{
	//print "Creating new BlackJack shoe ({$deck_count} decks)...\n\n" ;
	
	/*
	$n = count( $deck->get_all_cards() ) ;
	print "\nMultideck issued {$n} cards\n" ;
	
	$n = 1000 ;
	print "\nShuffling {$n} times..." ;
	$deck->shuffle( $n ) ;
	*/
	
	// begin use cases
	//print "\nDealing {$num_players} hands...\n" ;
	
	$cards_remaining = $deck->get_number_of_cards() ;
	// check the MultiDeck to ensure we have enough cards for this game, if not, then reissue the shoe and start a new game
	if( $cards_remaining < $necessary_card_count )
	{
		// start a new game with a new shoe
		print "\t\tCanceling game #{$game}; generating new shoe of {$deck_count} decks...\n" ;
		$deck = new MultiDeck( $deck_count, FALSE ) ;
		$deck->shuffle( $shuffle_count ) ;
		continue ;
	}
	/*
	else
	{
		print "\tStarting new game #{$game}; cards remaining in shoe: {$cards_remaining}\n" ;
	}
	*/
	
	// SETUP SOME HANDS
	$hands = array() ;
	
	// foreach card...
	for( $i=0; $i<$num_cards_per_hand; $i+=1 )
	{
		// foreach player...
		for( $j=0; $j<$num_players; $j+=1 )
		{
			// start a new hand for this player with the first card, or add to the existing hand $hands[$j]
			$hand = isset( $hands[$j] ) ? $hands[$j] : new BlackJackHand() ;
			
			$player_number = $j + 1 ;
			$card_number = $i + 1 ;
			
			//print "\tdealt to player {$player_number} (of $num_players} #{$card_number} card of {$num_cards_per_hand} cards per hand.\n" ;
			
			try
			{
				$hand->add_card( $deck->get_card() ) ;
			}
			catch( Exception $e )
			{
				// this should never happen
				die( "\n### Error: " . $e->getMessage() . "\n\n" ) ;
			}
			
			$hands[$j] = $hand ;
		}
	}
	
	$actual_game_count += 1 ;
	
	// for blackjack
	$max_hand_value = 21 ;
	
	// player opinion
	$player_min_hold_value = 17 ;
	
	// dealer face-up card values to hold against with 12-16
	$stay_on_dealer_cards = range( 2, 6, 1 ) ;
	
	// PLAY OUT THESE HANDS
	$num_of_hands = count($hands) ;
	
	// set last hand as the dealer (can be unset later)
	$hands[$num_of_hands - 1]->set_as_dealer_hand( TRUE ) ;
	
	// show dealer hand first before players decide what to do
	//print "\n\nDEALER Hand:\n" . $hands[$num_of_hands - 1]->get_as_string() . "\n" ;
	
	for( $i=0; $i<$num_of_hands; $i+=1 )
	{
		$hand = $hands[$i] ;
		
		// if we're dealing to the dealer now, flip the face-down card
		if( $hand->is_dealer_hand() )
		{
			//print "\nDealer Hand #{$i}:\n" ;	
			$hand->flip_dealer_card() ;
		}
		else
		{
			//print "\nHand #{$i}:\n" ;	
		}
		
		//print $hand->get_as_string() . "\n" ;
		$sum = $hand->get_card_sum_value() ;
		
		if( $hand->is_dealer_hand() )
		{
			$max_hand_value = 17 ;	// hard stop on 17, no more hits for the dealer
		}
		
		while( $sum < $max_hand_value && $sum < $player_min_hold_value ) // automatic stop at 17 for players
		//while( $sum < $max_hand_value && $draw === TRUE ) 	// ask for user input
		{
			try
			{
				//$draw = $hand->is_dealer_hand() || (bool)trim( fgets( STDIN ) ) ;	// comment out for automatic drawing
				$hand->add_card( $deck->get_card() ) ;
				$sum = $hand->get_card_sum_value() ;	
				//print $hand->get_as_string() . "\n"  ;
			}
			catch( Exception $e )
			{
				// this should never happen
				die( "\n### Error: " . $e->getMessage() . "\n\n" ) ;
			}
		}
		
		$result = "" ;
		
		if( $sum > 21 ) 
		{
			$result = "BUST" ;
			$bust_count += 1 ;
		}
		elseif( $sum === 21 && $hand->get_card_count() === 2 )
		{
			$result = "BLACKJACK!" ;
			$blackjack_count += 1 ;
		}
		else
		{
			$result = "HELD" ;
			$held_count += 1 ;
		}
		
		print $hand->get_as_string() . " -- {$result}\n" ;
	}
}

	print "\nGames attempted: {$game} (actual: {$actual_game_count})\nResults:\n" ;
	print "Held: {$held_count}\nBust: {$bust_count}\nBlackjack: {$blackjack_count}\n" ;
	
	$uniqid = uniqid() ;
	
	print "Game group id: {$uniqid}\n" ;
	$sql = "INSERT INTO blackjack_hands (group_id, game_count, bust_count, hold_count, blackjack_count, player_count) VALUES ('{$uniqid}', '{$actual_game_count}', '{$bust_count}', '{$held_count}', '{$blackjack_count}', '{$num_players}')" ;
	
	if( $mysqli->query( $sql ) === FALSE ) 
	{
		die( "Error: could not insert the game data into the db" ) ;
	}
	
	//print "\n\nCards remaining: " ;
	//print count( $deck->get_all_cards() );
	
// static example of a hand with an Ace resulting in ace reduction to 1
/*
$hand = new BlackJackHand() ;
$hand->add_card( new Card( 0, ACE ) ) ;
$hand->add_card( new Card( 0, 2 ) ) ;
$hand->add_card( new Card( 0, 2 ) ) ;
$hand->add_card( new Card( 0, KING ) ) ;
$hand->add_card( new Card( 0, 9 ) ) ;

print "\n\nnew hand: " . $hand->get_as_string() ;
print "\n" ;
*/

// static test of hand comparison
/*
$hand1 = new BlackJackHand() ;
$hand1->add_card( new Card( 0, ACE ) ) ;
$hand1->add_card( new Card( 0, 10 ) ) ;
$hand2 = new BlackJackHand() ;
$hand2->add_card( new Card( 0, ACE ) ) ;
$hand2->add_card( new Card( 0, 3 ) ) ;
$is_equal = (int) BlackJackHandEvaluator::compare_for_equality( $hand1, $hand2 ) ;
$hand1_bj_status = (int) BlackJackHandEvaluator::is_blackjack( $hand1 ) ;
$hand2_bj_status = (int) BlackJackHandEvaluator::is_blackjack( $hand2 ) ;

print "\nhand1: {$hand1->get_as_string()}\thand2: {$hand2->get_as_string()}\n" ;

print "is hand1 blackjack? {$hand1_bj_status}\n" ;
print "is hand2 blackjack? {$hand2_bj_status}\n" ;

print "are the hands equal? {$is_equal}\n" ;

if( !$is_equal )
{
	$highest_hand = BlackJackHandEvaluator::compare_for_highest_hand( $hand1, $hand2 ) ;
	print "the larger hand is: {$highest_hand->get_as_string()}\n" ;
}
*/

$duration = ( microtime( true ) - $start ) * 1000 ; 	// miliseconds
$end_mem = ( memory_get_usage() - $start_mem ) / 1024 ;

print "\n\nClosing DB connection..." ;
$mysqli->close();

print "\n\nTime elapsed (ms): {$duration}" ;	
print "\nMemory used (kb): {$end_mem}\n\n" ; 

?>
