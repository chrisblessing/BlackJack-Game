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
	private $is_face_down = FALSE ;
	
	public function __construct( $suit, $value )
	{
		$this->set_suit( $suit ) ;
		$this->set_value( $value ) ;
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
	
	public function hide_str_value()
	{
		$this->is_face_down = TRUE ;
	}
	
	public function show_str_value()
	{
		$this->is_face_down = FALSE ;
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
	
	public function get_as_string()
	{
		$suit_letter = ( $this->is_face_down ) ? '*' : SuitEvaluator::get_suit_letter( $this->get_suit() ) ;
		$card_value = ( $this->is_face_down ) ? '*' : CardValueEvaluator::get_value_letter( $this->value ) ;
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
			// get_all_cards isnt properly appended due to duplicate keys >:|
			$j = 0 ;
			while( isset( $cards[$j] ) )
			{
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
		
		$hand_str .= "\n" ;
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
	
	private $is_dealer_hand = FALSE ;
	private $dealer_card_flipped = FALSE ;
	
	public function __construct()
	{}
	
	protected static function reduce_next_ace_value( array &$cards )
	{
		foreach( $cards as &$card )
		{
			if( $card->get_value() == self::ACE_VALUE )
			{
				// try setting this Ace to '1' and re-summing
				$card->set_value( 1, TRUE ) ;
				return TRUE ;
			}
		}
		
		return FALSE ;
	}
	
	public function get_card_sum_value()
	{
		$sum = 0 ;
		$has_ace = FALSE ;
		
		foreach( $this->cards as $card )
		{
			$value = $card->get_value() ;
			$sum += $value ;
			
			if( self::ACE_VALUE === $value )
				$has_ace = TRUE ;
		}
		
		if( $has_ace && $sum > self::MAX_HAND_VALUE )
		{
			self::reduce_next_ace_value( $this->cards ) ;
			return $this->get_card_sum_value() ;
		}
		
		return $sum ;
	}
	
	public function set_as_dealer_hand( $is_dealer_hand )
	{
		$this->is_dealer_hand = (bool) $is_dealer_hand ;
		$this->cards[0]->hide_str_value() ;
	}
	
	public function is_dealer_hand()
	{
		return $this->is_dealer_hand ;
	}
	
	public function flip_dealer_card()
	{
		// flip the first card of the hand face up
		$this->cards[0]->show_str_value() ;
		$this->dealer_card_flipped = TRUE ;
	}
	
	public function is_dealer_card_up()
	{
		return $this->dealer_card_flipped ;
	}
	
	public function get_as_string()
	{
		$hand_str = "" ;
		
		foreach( $this->cards as $card )
		{
			$hand_str .= $card->get_as_string() . ' ' ;
		}
		
		if( $this->dealer_card_flipped || !$this->is_dealer_hand() )
		{
			$hand_str .= "\t[{$this->get_card_sum_value()}]" ;
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
