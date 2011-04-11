<?php

define( 'ACE', 14 ) ;
define( 'KING', 13 ) ;
define( 'QUEEN', 12 ) ;
define( 'JACK', 11 ) ;

define( 'CLUBS', 0 ) ;
define( 'HEARTS', 1 ) ;
define( 'SPADES', 2 ) ;
define( 'DIAMONDS', 3 ) ;

define( 'ERR_DECK_EMPTY', 'No cards left to deal.' ) ;
define( 'ERR_INVALID_CARD_VALUE', 'Invalid card value' ) ;
define( 'ERR_SEAT_UNAVAIL', 'Cannot sit where a player is already seated.' ) ;
define( 'ERR_TABLE_FULL', 'Cannot add more players; table is full.' ) ;
define( 'ERR_INVALID_PLAYER_OBJ', 'Invalid player object.' ) ;
define( 'ERR_DECK_COMPROMISED', 'Cannot reorder a deck once cards have been drawn; try reset().' ) ;
define( 'ERR_CANNOT_REMOVE_DEALER', 'Cannot remove dealer from table.' ) ;
define( 'ERR_TABLE_HAS_DEALER', 'Cannot replace dealer at table; start a new table instead.' ) ;

class CardValueEvaluator
{
	protected static $values = array(
		2, 3, 4, 5, 6, 7, 8, 9, 10, JACK, QUEEN, KING, ACE
	) ;
	
	protected static $face_card_names = array(
		JACK => array( "letter" => "J", "number" => 10 ), 
		QUEEN => array( "letter" => "Q", "number" => 10 ), 
		KING => array( "letter" => "K", "number" => 10 ), 
		ACE => array( "letter" => "A", "number" => 11 )
	) ;
	
	public static function is_valid( $value ) 
	{
		if( in_array( $value, self::$values ) )
		{
			return true ;
		}
		
		return false ;
	}
	
	public static function get_value_letter( $value )
	{
		if( self::is_valid( $value ) )
		{
			if( in_array( $value, array_keys( self::$face_card_names ) ) ) 
			{
				return self::$face_card_names[$value]['letter'] ;
			}
		}
		
		return $value ;
	}
	
	public static function get_all_values()
	{
		return self::$values ;
	}
	
	public static function get_numeric_value( $index_val )
	{
		if( in_array( $index_val, array_keys( self::$face_card_names  ) ) )
		{
			return self::$face_card_names[$index_val]['number'] ;
		}
		
		// else...
		return $index_val ;
	}
}

class BlackJackHandEvaluator
{
	public static function compare_for_equality( Hand $hand1, Hand $hand2 )
	{
		$hand1_value = $hand1->get_card_sum_value() ;
		$hand2_value = $hand2->get_card_sum_value() ;
		
		return ( $hand1_value  ===  $hand2_value ) ? TRUE : FALSE ;
	}
	
	public static function compare_for_highest_hand( Hand $hand1, Hand $hand2 )
	{
		$hand1_value = $hand1->get_card_sum_value() ;
		$hand2_value = $hand2->get_card_sum_value() ;
		
		return ( $hand1_value  >  $hand2_value ) ? $hand1 : $hand2 ;
	}
	
	public static function is_blackjack( Hand $hand )
	{
		$is_two_cards = ( $hand->get_card_count() === 2 ) ;
		
		if( $is_two_cards )
		{
			$value = $hand->get_card_sum_value() ;
			return ( $value === 21 ) ;			
		}
		
		return FALSE ;
	}
}


class SuitEvaluator
{
	private static $suits = array(
		"c" => CLUBS,
		"h" => HEARTS, 
		"s" => SPADES, 
		"d" => DIAMONDS
	) ;
	
	public static function is_valid( $value ) 
	{
		if( ( in_array( $value, self::$suits ) ) )
		{
			return true ;
		}
		
		return false ;
	}
	
	public static function get_suit_letter( $suit )
	{
		if( self::is_valid( $suit ) )
		{
			$suits = array_flip( self::$suits ) ;
			return $suits[$suit] ;
		}		
	}
	
	public static function get_all_suits()
	{
		return self::$suits ;
	}
}

class Card
{
	private $suit ;
	private $value ;
	
	public function __construct( $suit, $value, $override = FALSE )
	{
		$this->set_suit( $suit ) ;
		$this->set_value( $value, $override ) ;
	}
	
	public function set_suit( $suit )
	{
		if( SuitEvaluator::is_valid( $suit ) )
		{
			$this->suit = $suit ;
		}
	}
	
	public function set_value( $value, $override = FALSE )
	{
		if( CardValueEvaluator::is_valid( $value ) || $override === TRUE )
		{
			$this->value = $value ;
		}
		else
		{
			throw new Exception( ERR_INVALID_CARD_VALUE ) ;
		}
	}
	
	public function get_suit()
	{
		return $this->suit ;
	}
	
	public function get_value()
	{
		return CardValueEvaluator::get_numeric_value( $this->value ) ;
	}
	
	public function get()
	{
		return array( 
			'suit' => $this->suit, 
			'value' => $this->value 
		) ;
	}
	
	public function is_ace()
	{
		return ( $this->value === ACE ) ;
	}
	
	public function get_as_string( $is_face_down = FALSE )
	{
		$suit_letter = ( $is_face_down ) ? '*' : SuitEvaluator::get_suit_letter( $this->get_suit() ) ;
		$card_value = ( $is_face_down ) ? '*' : CardValueEvaluator::get_value_letter( $this->value ) ;
		return '{' . $card_value . ':' . $suit_letter . '}' ;
	}
	
	public function __toString()
	{
		return $this->get_as_string() ;
	}
}

class Deck
{
	protected $cards = array() ;
	protected $deck_count = 1 ;
	protected $drawn_cards = array() ;
	
	public function __construct( $cards = NULL, $shuffle = FALSE )
	{
		if( is_array( $cards ) && count( $cards ) > 0 )
		{
			print "{__CLASS__} would fill with given array" ;
		}
		else
		{
			// build a standard 52-card deck
			foreach( SuitEvaluator::get_all_suits() as $suit )
			{
				foreach( CardValueEvaluator::get_all_values() as $value )
				{
					$this->cards[] = new Card( $suit, $value ) ;
				}
			}
			
			if( $shuffle === TRUE )
			{
				$this->shuffle() ;
			}
		}
	}
	
	public function reorder()
	{
		if( count( $this->drawn_cards === 0 ) )
		{
			natsort( $this->cards ) ;		
		}
		else
		{
			throw new Exception( ERR_DECK_COMPROMISED ) ;
		}	}
	
	public function get_card()
	{
		$next_card = $this->get_number_of_cards() - 1 ;
		if( $next_card < 0 ) 
		{
			throw new Exception( ERR_DECK_EMPTY ) ;
		}
		
		$card = $this->cards[$next_card] ;
		unset( $this->cards[$next_card] ) ;
		
		$this->add_drawn_card( $card ) ;
		return $card ;
	}
	
	public function add_drawn_card( Card $card )
	{
		$this->drawn_cards[] = $card ;
	}
	
	public function get_number_of_cards()
	{
		return count( $this->cards ) ;
	}
	
	public function add_card( Card $card )
	{}
	
	public function get_deck_count()
	{
		return $this->deck_count ;
	}
	
	public function reset()
	{
		foreach( $this->drawn_cards as $card )
		{
			$this->cards[] = $card ;
		}
		
		//$count = count($this->cards) ;
		//print "reset deck to {$count} cards...\n" ;
		
		$this->drawn_cards = array() ;
	}
	
	public function shuffle( $n = 1 )
	{
		$n = (int)$n ;
		for( $i=$n; $i>0; $i-- )
		{
			shuffle( $this->cards ) ;	
		}
	}
	
	public function get_all_cards()
	{
		return $this->cards ;
	}
	
	public function get_as_string()
	{
		$count = count( $this->cards ) ;
		$deck_str = "\n### Printing deck... \n\tCards avail [{$count}]:\n" ;
		
		natsort( $this->cards ) ;
		foreach( $this->cards as $card )
		{
			$deck_str .= $card->get_as_string() . " " ;
		}

		$count = count( $this->drawn_cards ) ;
		$deck_str .= "\n\n\tCards drawn [{$count}]:\n" ;
		
		natsort( $this->drawn_cards ) ;
		foreach( $this->drawn_cards as $card )
		{
			$deck_str .= $card->get_as_string() . " " ;			
		}
		
		$deck_str .= "" ;
		
		return $deck_str ;
	}
	
	public function cut()
	{
		// cuts the deck randomly between (card count*.2) and (card count*.8)
		$card_count = $this->get_number_of_cards() ;
		$lower_bound = round( $card_count * 0.2, 0 ) ;
		$upper_bound = round( $card_count * 0.8, 0 ) ;
		$cut_index = (int) rand( $lower_bound, $upper_bound ) ;
		
		// cut it up: top to bottom
		$this->cards = array_slice( $this->cards, $cut_index, NULL, TRUE ) + array_slice( $this->cards, 0, $cut_index, TRUE ) ;
	}
}

class MultiDeck extends Deck
{
	public function __construct( $n, $shuffle = FALSE )
	{
		$n = (int)$n ;
		$this->deck_count = $n ;
		
		$deck = new Deck( NULL, $shuffle ) ;
		$cards = $deck->get_all_cards() ;
			
		for( $i=0; $i<$n; $i++ )
		{
			// get a collection of references to the original deck cards, we're not duplicating cards here
			
			// get_all_cards isnt properly appended due to duplicate keys >:|
			$j = 0 ;
			while( isset( $cards[$j] ) )
			{
				// test of card copying yields significant performance decrease over recycling
				//$card = $cards[$j] ;
				//$this->cards[] = new Card( $card->get_suit(), $card->get_value() ) ;
				
				// by reference instead...
				$this->cards[] = $cards[$j] ;
				$j++ ;
			}
		}
	}
}

class Hand
{
	protected $cards = array() ;
	
	public function __construct()
	{}
	
	public function add_card( Card $card )
	{
		$this->cards[] = $card ;
	}
	
	public function get_card_sum_value()
	{
		$sum = 0;
		foreach( $this->cards as $card )
		{
			$sum += $card->get_value() ;
		}
		
		return $sum ;
	}
	
	public function get_card_count()
	{
		return count( $this->cards ) ;
	}
	
	public function get_as_string()
	{
		$hand_str = "" ;
		
		foreach( $this->cards as $card )
		{
			$hand_str .= $card->get_as_string() . " " ;
		}
		
		return $hand_str ;
	}
	
	public function __toString()
	{
		return $this->get_as_string() ;
	}
}

class BlackJackHand extends Hand
{
	const ACE_VALUE = 11 ;
	const MAX_HAND_VALUE = 21 ;
	
	protected $dealer_hand = FALSE ;
	protected $drawing = FALSE ;
	protected $final = FALSE ;
	
	public function __construct()
	{}
	
	public function get_card_sum_value()
	{
		// this method does not keep a static score; it will re-compute based on any new cards added
		
		$sum = 0 ;
		$aces = 0 ;
		
		// first we need a total worst-case summation to see if we have to reduce aces
		foreach( $this->cards as $card )
		{
			$value = $card->get_value() ;
			$sum += $value ;
			
			if( $card->get_value() === self::ACE_VALUE )
			{
				$aces += 1 ;
			}
		}
		
		// if we had aces available for reduction, try until we are below the sum threshold
		if( $sum > self::MAX_HAND_VALUE && $aces > 0 )
		{
			for( $i=0; $i<$aces; $i+=1 )
			{
				$sum -= 10 ;
				
				if( $sum <= self::MAX_HAND_VALUE )
				{
					return $sum ;
				}			
			}
		}
		
		return $sum ;
	}
	
	public function set_as_dealer_hand( $is_dealer_hand )
	{
		$this->dealer_hand = (bool) $is_dealer_hand ;
	}
	
	public function is_dealer_hand()
	{
		return $this->dealer_hand ;
	}
	
	public function set_as_drawing()
	{
		$this->drawing = TRUE ;
	}
	
	public function set_as_final()
	{
		$this->final = TRUE ;
	}

	public function is_drawing()
	{
		return $this->drawing ;
	}
	
	public function is_final()
	{
		return $this->final ;
	}
	
	public function get_as_string()
	{
		$hand_str = "" ;
		$card_count = count( $this->cards ) ;
		
		for( $i=0; $i<$card_count; $i+=1 )
		{
			$card = $this->cards[$i] ;
			
			// if this is a dealer hand and it's the first card and there's no drawing yet, hide the first card
			if( $this->is_dealer_hand() && $i === 0 && !$this->is_drawing() )
			{
				// first card face down
				$hand_str .= $card->get_as_string( TRUE ) . ' ' ;
			}
			else
			{
				$hand_str .= $card->get_as_string() . ' ' ;
			}
		}
		
		return $hand_str ;
	}
}

abstract class Table
{
	protected $num_seats = 6 ;			// constructor will always accept a new table size
	protected $seats = array() ;		// array of Players (can be more than one seat to a player)
	protected $active_seat = NULL ;		// index to $seats for active player
	
	public function get_seat_order() {}
	public function deal_card_to_player( Player $player ) {}
	public function get_player_hand_sum( Player $player ) {}
	
	protected function is_seat_available( $seat_index )
	{
		return !isset( $this->seats[$seat_index] ) ;
	}
	
	protected function is_valid_seat_index( $seat_index ) 
	{
		return is_int( $seat_index ) && $seat_index >= 0 && $seat_index < $this->num_seats ;
	}
	
	protected function is_table_full()
	{
		return count( $this->seats ) === $this->num_seats ;
	}
	
	protected function set_active_seat( $seat_index ) 
	{
		if( $this->is_valid_seat_index( $seat_index ) )
		{
			$this->active_seat = $seat_index ;
		}
	}
	
	public function get_current_active_seat()
	{
		return $this->active_seat ;
	}
	
	public function get_next_available_seat()
	{
		$avail_seats = $this->get_available_seats() ;
		
		if( count( $avail_seats ) > 0 )
		{
			return $avail_seats[0] ;
		}
		
		throw new Exception( ERR_TABLE_FULL ) ;
	}
	
	public function get_available_seats()
	{
		$num_seats = $this->num_seats ;
		$avail_seats = array() ;
		
		for( $i=0; $i<$num_seats; $i++ )
		{
			if( $this->is_seat_available( $i ) )
			{
				$avail_seats[] = $i ;
			}
		}
		
		return $avail_seats ;
	}
	
	protected function set_dealer_seat( Player $player )
	{
		$has_dealer = isset( $this->seats[$this->num_seats - 1] ) && $this->seats[$this->num_seats - 1]->is_dealer() ;
		
		if( !$has_dealer )
		{
			$this->seats[$this->num_seats - 1] = $player ;
		}
		else
		{
			throw new Exception( ERR_TABLE_HAS_DEALER ) ;
		}
	}
	
	public function seat_player( Player $player, $seat_index = NULL ) 
	{
		if( $this->is_table_full() )
		{
			throw new Exception( ERR_TABLE_FULL ) ;
		}
		
		if( !empty( $player ) )
		{
			if( $player->is_dealer() )
			{
				$this->set_dealer_seat( $player ) ;
			}
			elseif( $this->is_valid_seat_index( $seat_index ) && $this->is_seat_available( $seat_index ) )
			{
				$this->seats[$seat_index] = $player ;
			}
			else
			{
				$seat_index = $this->get_next_available_seat() ;
				
				if( $seat_index !== FALSE )
				{
					$this->seats[$seat_index] = $player ;				
				}
				else
				{
					throw new Exception( ERR_SEAT_UNAVAIL ) ;
				}
			}
			
			// if this is the first player at the table, they are the active seat and first to act (must not be dealer)
			if( !$player->is_dealer() && $this->get_current_active_seat() === NULL )
			{
				$this->set_active_seat( $seat_index ) ;
			}
		}
		else
		{
			throw new Exception( ERR_INVALID_PLAYER_OBJ ) ;
		}
	}
	
	// removes a player from the table completely, all seats
	public function remove_player( Player $player )
	{
		foreach( $this->seats as $player_index => $current_player )
		{
			if( $player->is_dealer() )
			{
				throw new Exception( ERR_CANNOT_REMOVE_DEALER ) ;
			}
			
			if( $player == $current_player )
			{
				unset( $this->seats[$player_index] ) ;
			}
		}
	}
	
	public function remove_player_from_seat( $seat_index )
	{
		if( $this->is_valid_seat_index( $seat_index ) )
		{
			$player = $this->seats[$seat_index] ;
			if( !$player->is_dealer() )
			{
				unset( $this->seats[$seat_index] ) ;
				
				if( $this->active_seat === $seat_index )
				{
					$this->set_next_occupied_seat_active() ;
				}
			}
			else
			{
				throw new Exception( ERR_CANNOT_REMOVE_DEALER ) ;
			}
		}
	}
	
	public function set_next_occupied_seat_active()
	{
		$this->set_active_seat( $this->get_next_occupied_seat( $this->active_seat ) ) ;
	}
	
	public function get_next_occupied_seat( $from_seat_index )
	{
		if( $this->is_valid_seat_index( $from_seat_index ) )
		{
			if( $from_seat_index === $this->num_seats - 1 )
				$from_seat_index = -1 ;
				
			for( $i=$from_seat_index+1; $i<$this->num_seats; $i++ )
			{
				if( !$this->is_seat_available( $i ) )
				{
					return $i ;
				}
			}
		}
	}
}

class GenericTable extends Table
{
	public function __construct( $n = 1 )
	{
		// note: no default dealer here
		$this->num_seats = (int) $n ;
	}
}

class BlackJackTable extends Table
{
	public function __construct( $n = 6, array $players = NULL )
	{
		// create $n seats + 1 for the dealer (so 0-$n = $n+1)
		$this->num_seats = (int) $n + 1 ;
		
		// auto-add a dealer to this BlackJack Table
		$this->seat_player( new Dealer( 'Dealer' ) ) ;	// seat the dealer of course, last seat avail
		
		// if Players were supplied, populate the table
		if( !empty( $players ) )
		{
			foreach( $players as $player )
			{
				$this->seat_player( $player ) ;
			}
		}
	}
	
}

class Player
{
	public function __construct( $name = NULL )
	{
		$this->set_name( $name ) ;
	}
	
	public function is_dealer()
	{
		return $this instanceOf Dealer ;
	}
	
	public function set_name( $name = NULL )
	{
		if( !empty( $name ) )
		{
			$this->name = $name ;
		}
		else
		{
			$this->name = uniqid() ;
		}
	}
	
	public function get_name()
	{
		return $this->name ;
	}
	
	public function add_hand( Hand $hand )
	{
		
	}
	
	public function remove_hand()
	{}
	
	public function track_stat( $key, $value )
	{
		// flesh out this key/value business for player stats during a game, or long-term
	}
	
	public function get_stat( $key )
	{
		
	}
	
	public function get_all_stats()
	{
		
	}
}

class Dealer extends Player
{
	public function __construct( $name = NULL )
	{
		$this->set_name( $name ) ;
	}
}

?>
