<?php
namespace App\Support;

class MpLocations
{
    public static function districts(): array
    {
        return array_keys(self::data());
    }

    public static function tehsils(string $district): array
    {
        return self::data()[$district] ?? [];
    }

    public static function data(): array
    {
        return [
            'Agar Malwa'    => ['Agar','Susner','Badod','Nalkheda','Pachore'],
            'Alirajpur'     => ['Alirajpur','Jobat','Sondwa','Bhabra','Katthiwara','Udaigarh'],
            'Anuppur'       => ['Anuppur','Pushparajgarh','Jaithari','Kotma'],
            'Ashoknagar'    => ['Ashoknagar','Chanderi','Mungaoli','Isagarh'],
            'Balaghat'      => ['Balaghat','Baihar','Birsa','Katangi','Kirnapur','Lanji','Paraswada','Waraseoni'],
            'Barwani'       => ['Barwani','Pansemal','Pati','Rajpur','Niwali','Sendhwa','Thikri'],
            'Betul'         => ['Betul','Amla','Bhainsdehi','Ghoradongri','Multai','Shahpur','Athner','Chiraidongri'],
            'Bhind'         => ['Bhind','Ater','Gohad','Lahar','Mehgaon','Mihona','Raun'],
            'Bhopal'        => ['Bhopal','Berasia','Huzur','Phanda'],
            'Burhanpur'     => ['Burhanpur','Khaknar','Nepanagar'],
            'Chhatarpur'    => ['Chhatarpur','Bijawar','Buxwaha','Gaurihar','Harpalpur','Laundi','Maharajpur','Nowgong','Rajnagar'],
            'Chhindwara'    => ['Chhindwara','Amarwara','Bichhua','Chaurai','Harrai','Jamai','Junnardev','Mohkhed','Pandhurna','Parasia','Sausar','Tamia','Umranala'],
            'Damoh'         => ['Damoh','Batiyagarh','Hatta','Jabera','Patera','Patharia','Tendukheda'],
            'Datia'         => ['Datia','Bhander','Indergarh','Seondha'],
            'Dewas'         => ['Dewas','Bagli','Kannod','Khategaon','Sonkatch','Tonkkhurd','Udainagar'],
            'Dhar'          => ['Dhar','Badnawar','Dharampuri','Gandhwani','Kukshi','Manawar','Nalcha','Pithampur','Sardarpur','Tirla','Umarban'],
            'Dindori'       => ['Dindori','Amarpur','Bajag','Gadasarai','Karanjia','Mehandwani','Samnapur','Shahpura'],
            'Guna'          => ['Guna','Aron','Bamori','Chachoda','Kumbhraj','Raghogarh'],
            'Gwalior'       => ['Gwalior','Bhitarwar','Dabra','Morar','Pichhore'],
            'Harda'         => ['Harda','Hansalpur','Khirkiya','Sirali','Timarni'],
            'Hoshangabad'   => ['Hoshangabad','Babai','Bankhedi','Itarsi','Kesla','Pachmarhi','Pipariya','Seoni-Malwa','Sohagpur'],
            'Indore'        => ['Indore','Depalpur','Hatod','Mhow','Sanwer'],
            'Jabalpur'      => ['Jabalpur','Jabalpur Rural','Kundam','Majholi','Panagar','Patan','Shahpura','Sihora'],
            'Jhabua'        => ['Jhabua','Meghnagar','Petlawad','Rama','Ranapur','Thandla'],
            'Katni'         => ['Katni','Bahoriband','Dhimarkheda','Mudwara','Rithi','Vijayraghavgarh'],
            'Khandwa'       => ['Khandwa','Harsud','Khalwa','Mandhata','Mundi','Pandhana','Punasa'],
            'Khargone'      => ['Khargone','Barwaha','Bhagwanpura','Bhikangaon','Gogawan','Jhirniya','Kasrawad','Maheshwar','Mandleshwar','Segaon','Zirapur'],
            'Mandla'        => ['Mandla','Baichhan','Bichhiya','Ghughari','Mawai','Mohgaon','Narayanganj','Niwas'],
            'Mandsaur'      => ['Mandsaur','Bhanpura','Garoth','Malhargarh','Narayangarh','Sitamau','Suwasra'],
            'Morena'        => ['Morena','Ambah','Joura','Kailaras','Porsa','Sabalgadh','Sumawali'],
            'Narsinghpur'   => ['Narsinghpur','Chichli','Gadarwara','Gotegaon','Kareli','Saikheda','Tendukheda'],
            'Neemuch'       => ['Neemuch','Jawad','Manasa','Singoli'],
            'Niwari'        => ['Niwari','Orchha','Prithvipur'],
            'Panna'         => ['Panna','Ajaigarh','Gunnor','Pawai','Shahnagar'],
            'Raisen'        => ['Raisen','Bareli','Begumganj','Goharganj','Obaidullaganj','Sanchi','Silwani','Udaipura'],
            'Rajgarh'       => ['Rajgarh','Biaora','Khilchipur','Narsinghgarh','Pachore','Sarangpur','Suthaliya'],
            'Ratlam'        => ['Ratlam','Alot','Jaora','Piploda','Sailana'],
            'Rewa'          => ['Rewa','Gangev','Gurh','Hanumana','Mauganj','Naigarhi','Sirmour','Teonthar'],
            'Sagar'         => ['Sagar','Banda','Bina','Deori','Jaisinagar','Khurai','Malthon','Rahatgarh','Rehli','Shahgarh'],
            'Satna'         => ['Satna','Amarpatan','Maihar','Nagod','Rampur Baghelan','Ramnagar','Uchehara'],
            'Sehore'        => ['Sehore','Ashta','Budhni','Icchawar','Nasrullaganj','Rehti'],
            'Seoni'         => ['Seoni','Barghat','Chhapara','Ghansore','Keolari','Kurai','Lakhnadon'],
            'Shahdol'       => ['Shahdol','Beohari','Burhar','Jaisinghnagar','Jaitpur','Sohagpur'],
            'Shajapur'      => ['Shajapur','Agar','Kalapipal','Makdon','Moman Badodiya','Nalkheda','Shujalpur'],
            'Sheopur'       => ['Sheopur','Karahal','Vijaypur'],
            'Shivpuri'      => ['Shivpuri','Badarwas','Karera','Khaniadhana','Narwar','Pichhore','Pohri'],
            'Sidhi'         => ['Sidhi','Churhat','Deosar','Majhauli','Rampur Naikin'],
            'Singrauli'     => ['Singrauli','Baidhan','Chitrangi','Devsar'],
            'Tikamgarh'     => ['Tikamgarh','Baldeogarh','Jatara','Khargapur','Lidhora','Palera','Prithvipur'],
            'Ujjain'        => ['Ujjain','Barnagar','Ghattia','Khachrod','Mahidpur','Nagda','Tarana'],
            'Umaria'        => ['Umaria','Bandhogarh','Chandia','Manpur','Pali'],
            'Vidisha'       => ['Vidisha','Basoda','Ganjbasoda','Gyaraspur','Kurwai','Lateri','Nateran','Sironj'],
        ];
    }
}
