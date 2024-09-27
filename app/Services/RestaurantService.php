<?php

namespace App\Services;

use App\Models;
use App\Constants\HttpStatusCodes;
use App\Models\Restaurant;
use App\Models\RestaurantOperationHour;
use Carbon\Carbon;
use DB;
use Exception;

class RestaurantService
{
    public function getDatatable($request, $meta)
    {
        $query = Restaurant::query()->with('operationHours');

        if ($request->search) {
            $query->whereRaw("LOWER(name) LIKE ?", "%".trim(strtolower($request->search))."%");
        }

        if ($request->datetime) {
            $selectedDate = Carbon::parse($request->datetime)->format('N');
            $formatTime = Carbon::parse($request->datetime)->format('H:i:s');
            $query->whereHas('operationHours', function($q) use ($selectedDate, $selectedtime) {
                $q->where('day', $selectedDate)
                    ->whereTime('open', '<=', $selectedtime)
                    ->whereTime('close', '>=', $selectedtime)
                    ->where('is_open', true);
            });
        }

        $restaurants = $query->orderBy('name', $meta['orderBy'])
                    ->paginate($meta['limit']);;

        foreach ($restaurants as $indexResto => $restaurant) {
            $hoursString = "";
            $latestDataArray = [];

            foreach ($restaurant->operationHours as $i => $operationHour) {
                $latestData = end($latestDataArray);
                
                if (empty($latestData)) {
                    $newArray = [
                        'day' => [$operationHour->day],
                        'label' => [$this->getDayName($operationHour->day)],
                        'open' => $operationHour->open,
                        'close' => $operationHour->close
                    ];
                    $latestDataArray[] = $newArray;
                } else {
                    $latestDay = end($latestData['day']);
                    
                    if ($operationHour->day - $latestDay === 1) {
                        if ($latestData['open'] === $operationHour->open
                            && $latestData['close'] === $operationHour->close
                        ) {
                            $latestData['day'][] = $operationHour->day;
                            $latestData['label'][] = $this->getDayName($operationHour->day);
                            $latestDataArray[count($latestDataArray) - 1] = $latestData;
                        } else {
                            $newArray = [
                                'day' => [$operationHour->day],
                                'label' => [$this->getDayName($operationHour->day)],
                                'open' => $operationHour->open,
                                'close' => $operationHour->close
                            ];
                            $latestDataArray[] = $newArray;
                        }
                    } else {
                        $newArray = [
                            'day' => [$operationHour->day],
                            'label' => [$this->getDayName($operationHour->day)],
                            'open' => $operationHour->open,
                            'close' => $operationHour->close
                        ];
                        $latestDataArray[] = $newArray;
                    }
                }
            }

            $stringDay = '';

            foreach ($latestDataArray as $index => $latestA) {

                if ($index > 0) {
                    $stringDay .= "\n/ ";
                }
                $newString = "{$latestA['label'][0]}";
                if (count($latestA['day']) > 1) {
                    $latestB = end($latestA['label']);
                    $newString .= " - {$latestB}";
                }

                $latestDataOpen =  Carbon::createFromFormat('H:i:s', $latestA['open'])->format('h:i a') ;
                $latestDataClose = Carbon::createFromFormat('H:i:s', $latestA['close'])->format('h:i a') ;; 

                $newString .= " {$latestDataOpen} - {$latestDataClose}";

                $stringDay .= $newString;
            }

            $restaurants[$indexResto]['schedule_label'] = $stringDay;
        }

        $meta = [
            'total'        => $restaurants->total(),
            'count'        => $restaurants->count(),
            'per_page'     => $restaurants->perPage(),
            'current_page' => $restaurants->currentPage(),
            'total_pages'  => $restaurants->lastPage()
        ];

        return ['data' => $restaurants->items(), 'meta' => $meta];
        
    }

    public function getDetailByID($id)
    {
        return Restaurant::select()
            ->with('operationHours')
            ->where('id', $id)
            ->first();
    }

    public function store($request)
    {
        try {
            $name = $request->name;
            $address = $request->address;
            $operationHours = $request->operation_hours;

            return DB::transaction(function () use ($name, $address, $operationHours) {

                $restaurant = new Restaurant();
                $restaurant->name = $name;
                $restaurant->address = $address;
                $restaurant->save();

                foreach($operationHours as $operationHour) {
                    $restoOperationHour = new RestaurantOperationHour();
                    $restoOperationHour->restaurant_id = $restaurant->id;
                    $restoOperationHour->day = $operationHour['day'];
                    $restoOperationHour->open = $operationHour['open'];
                    $restoOperationHour->close = $operationHour['close'];
                    $restoOperationHour->is_open = $operationHour['is_open'];
                    $restoOperationHour->save();
                }

                return $restaurant;
            });
        } catch (\Exception $e) {
            \Log::error('Error creating schedule master: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    public function update($id, $request)
    {
        try {
            $name = $request->name;
            $address = $request->address;
            $operationHours = $request->operation_hours;

            return DB::transaction(function () use ($id, $name, $address, $operationHours) {

                $restaurant = $this->getDetailByID($id);
                $restaurant->name = $name;
                $restaurant->address = $address;
                $restaurant->save();

                $oldOperationHours = RestaurantOperationHour::where('restaurant_id', $id)->delete();

                foreach($operationHours as $operationHour) {
                    $restoOperationHour = new RestaurantOperationHour();
                    $restoOperationHour->restaurant_id = $id;
                    $restoOperationHour->day = $operationHour['day'];
                    $restoOperationHour->open = $operationHour['open'];
                    $restoOperationHour->close = $operationHour['close'];
                    $restoOperationHour->is_open = $operationHour['is_open'];
                    $restoOperationHour->save();
                }

                return $restaurant->refresh();
            });
        } catch (\Exception $e) {
            \Log::error('Error creating schedule master: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    public function delete($id)
    {
        $oldDataStudent = $this->getDetailByID($id);

        if ($oldDataStudent === null) {
            return $oldDataStudent;
        }

        $oldDataStudent->delete();
        $oldDataStudent->save();

        return $oldDataStudent;
    }

    private function getDayName($day)
    {
        $days = [
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
            7 => 'Sun',
        ];

        return $days[$day] ?: 'Unknown';
    }
}