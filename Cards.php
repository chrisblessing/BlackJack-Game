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
	private $drawn_cards = array() ;
	
	public function __construct( $cards = NULL, $shuffle = TRUE )
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
	public function __construct( $n, $shuffle = TRUE )
	{
		$n = (int)$n ;
		$deck = new Deck( NULL, $shuffle ) ;
		$cards = $deck->get_all_cards() ;
			
		for( $i=0; $i<$n; $i++ )
		{
			// get a collection of references to the original deck cards, we're not duplicating cards here
			
			// get_all_cards isnt properly appended due to duplicate keys >:|
			$j = 0 ;
			while( isset( $cards[$j] ) )
			{
				// test of card copying yields significant performance decrease 
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

abstract class Game
{
	// each seat can hold one Player
	protected $seats = array() ;
	
	protected function add_player( Player $player ) {}
	protected function remove_player( Player $player ) {}
	protected function get_player_order() {}
	
}

class GameSeat
{
	protected $player ;
	
	public function __construct( Player $player )
	{
		$this->seat_player( $player ) ;
	}
	
	public function seat_player( Player $player ) 
	{
		if( empty( $this->player ) )
		{
			$this->player = $player ;
		}
		else
		{
			throw new Exception( ERR_SEAT_UNAVAIL ) ;
		}
	}
	
	public function remove_player()
	{
		unset( $this->player ) ;
	}
}

class BlackJackGame extends Game
{
	protected $seats ;
	
	public function __construct()
	{
		// create $n seats + 1 for the dealer (so 0-$n = $n+1)
		
		// need to create methodology for setting up empty table, then allowing seats to be occupied
		$this->seats = range( 0, $n, 1 ) ;
	}
	
}

class Player
{
	public function __construct()
	{}
	
	public function set_name( $name = '' )
	{}
	
	public function get_name()
	{}
	
	public function add_hand()
	{}
	
	public function remove_hand()
	{}
	
	public function track_stat( $key, $value )
	{
		// flesh out this key/value business for player stats during a game, or long-term
	}
}

?>
