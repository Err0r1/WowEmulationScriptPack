/*
Transmogrification NPC 2.4.3
Made for Flawless WoW. Originally made for 3.3.5, ported by Hufsa.
*/

#define GOLD_COST    0 // 0 for no gold cost

#include "ScriptPCH.h"
#include "ObjectMgr.h"
#include "Language.h"
#include "Item.h"

#if (GOLD_COST)
#define GOLD_COST_FUNCTION GetFakePrice(oldItem)
#else
#define GOLD_COST_FUNCTION 0
#endif

const char * GetSlotName(uint8 slot, WorldSession* session)
{
    switch (slot)
    {
        case EQUIPMENT_SLOT_HEAD      : return session->GetSkyFireString(LANG_SLOT_NAME_HEAD);
        case EQUIPMENT_SLOT_SHOULDERS : return session->GetSkyFireString(LANG_SLOT_NAME_SHOULDERS);
        case EQUIPMENT_SLOT_BODY      : return session->GetSkyFireString(LANG_SLOT_NAME_BODY);
        case EQUIPMENT_SLOT_CHEST     : return session->GetSkyFireString(LANG_SLOT_NAME_CHEST);
        case EQUIPMENT_SLOT_WAIST     : return session->GetSkyFireString(LANG_SLOT_NAME_WAIST);
        case EQUIPMENT_SLOT_LEGS      : return session->GetSkyFireString(LANG_SLOT_NAME_LEGS);
        case EQUIPMENT_SLOT_FEET      : return session->GetSkyFireString(LANG_SLOT_NAME_FEET);
        case EQUIPMENT_SLOT_WRISTS    : return session->GetSkyFireString(LANG_SLOT_NAME_WRIST);
        case EQUIPMENT_SLOT_HANDS     : return session->GetSkyFireString(LANG_SLOT_NAME_HANDS);
        case EQUIPMENT_SLOT_BACK      : return session->GetSkyFireString(LANG_SLOT_NAME_BACK);
        case EQUIPMENT_SLOT_MAINHAND  : return session->GetSkyFireString(LANG_SLOT_NAME_MAINHAND);
        case EQUIPMENT_SLOT_OFFHAND   : return session->GetSkyFireString(LANG_SLOT_NAME_OFFHAND);
        case EQUIPMENT_SLOT_RANGED    : return session->GetSkyFireString(LANG_SLOT_NAME_RANGED);
        case EQUIPMENT_SLOT_TABARD    : return session->GetSkyFireString(LANG_SLOT_NAME_TABARD);
        default:
            return NULL;
    }
}

std::string GetItemName(Item* item, WorldSession* session)
{
    std::string name = item->GetProto()->Name1;

    return name;
}

std::map<uint64, std::map<uint32, Item*> > _items; // _items[lowGUID][DISPLAY] = item

bool OnGossipHello(Player* player, Creature* creature)
{
    WorldSession* session = player->GetSession();

    for (uint8 slot = EQUIPMENT_SLOT_START; slot < EQUIPMENT_SLOT_TABARD; slot++) // EQUIPMENT_SLOT_END
    {
        if (Item* newItem = player->GetItemByPos(INVENTORY_SLOT_BAG_0, slot))
        {
            if (newItem->HasGoodFakeQuality())
            {
                if (const char* slotName = GetSlotName(slot, session))
                    player->ADD_GOSSIP_ITEM(GOSSIP_ICON_TRAINER, slotName, EQUIPMENT_SLOT_END, slot);
            }
        }
    }

    player->ADD_GOSSIP_ITEM_EXTENDED(GOSSIP_ICON_INTERACT_1, session->GetSkyFireString(LANG_OPTION_REMOVE_ALL), EQUIPMENT_SLOT_END+2, 0, session->GetSkyFireString(LANG_POPUP_REMOVE_ALL), 0, false);
    player->ADD_GOSSIP_ITEM(GOSSIP_ICON_TALK, session->GetSkyFireString(LANG_OPTION_UPDATE_MENU), EQUIPMENT_SLOT_END+1, 0);
    player->SEND_GOSSIP_MENU(DEFAULT_GOSSIP_MESSAGE, creature->GetGUID());
    return true;
}

bool OnGossipSelect(Player* player, Creature* creature, uint32 sender, uint32 uiAction)
{
    WorldSession* session = player->GetSession();
    player->PlayerTalkClass->ClearMenus();

    switch(sender)
    {
    case EQUIPMENT_SLOT_END: // Show items you can use
        {
            if (Item* oldItem = player->GetItemByPos(INVENTORY_SLOT_BAG_0, uiAction))
            {
                uint32 lowGUID = player->GetGUIDLow();
                _items[lowGUID].clear();
                uint32 limit = 0;

                for (uint8 i = INVENTORY_SLOT_ITEM_START; i < INVENTORY_SLOT_ITEM_END; i++)
                {
                    if (limit > 30)
                        break;

                    if (Item* newItem = player->GetItemByPos(INVENTORY_SLOT_BAG_0, i))
                    {
                        uint32 display = newItem->GetProto()->DisplayInfoID;
                        if (player->SuitableForTransmogrification(oldItem, newItem) == ERR_FAKE_OK)
                        {
                            if (_items[lowGUID].find(display) == _items[lowGUID].end())
                            {
                                limit++;
                                _items[lowGUID][display] = newItem;
                                player->ADD_GOSSIP_ITEM_EXTENDED(GOSSIP_ICON_INTERACT_1, GetItemName(newItem, session), uiAction, display, session->GetSkyFireString(LANG_POPUP_TRANSMOGRIFY)+GetItemName(newItem, session), GOLD_COST_FUNCTION, false);
                            }
                        }
                    }
                }

                for (uint8 i = INVENTORY_SLOT_BAG_START; i < INVENTORY_SLOT_BAG_END; i++)
                {
                    if (Bag* bag = player->GetBagByPos(i))
                    {
                        for (uint32 j = 0; j < bag->GetBagSize(); j++)
                        {
                            if (limit > 30)
                                break;
                            if (Item* newItem = player->GetItemByPos(i, j))
                            {
                                uint32 display = newItem->GetProto()->DisplayInfoID;
                                if (player->SuitableForTransmogrification(oldItem, newItem) == ERR_FAKE_OK)
                                {
                                    if (_items[lowGUID].find(display) == _items[lowGUID].end())
                                    {
                                        limit++;
                                        _items[lowGUID][display] = newItem;
                                        player->ADD_GOSSIP_ITEM_EXTENDED(GOSSIP_ICON_INTERACT_1, GetItemName(newItem, session), uiAction, display, session->GetSkyFireString(LANG_POPUP_TRANSMOGRIFY)+GetItemName(newItem, session), GOLD_COST_FUNCTION, false);
                                    }
                                }
                            }
                        }
                    }
                }

                char popup[250];
                snprintf(popup, 250, session->GetSkyFireString(LANG_POPUP_REMOVE_ONE), GetSlotName(uiAction, session));
                player->ADD_GOSSIP_ITEM_EXTENDED(GOSSIP_ICON_INTERACT_1, session->GetSkyFireString(LANG_OPTION_REMOVE_ONE), EQUIPMENT_SLOT_END+3, uiAction, popup, 0, false);
                player->ADD_GOSSIP_ITEM(GOSSIP_ICON_TALK, session->GetSkyFireString(LANG_OPTION_BACK), EQUIPMENT_SLOT_END+1, 0);
                player->SEND_GOSSIP_MENU(DEFAULT_GOSSIP_MESSAGE, creature->GetGUID());
            }
            else
                OnGossipHello(player, creature);
        } break;
    case EQUIPMENT_SLOT_END+1: // Back
        {
            OnGossipHello(player, creature);
        } break;
    case EQUIPMENT_SLOT_END+2: // Remove Transmogrifications
        {
            bool removed = false;
            for (uint8 Slot = EQUIPMENT_SLOT_START; Slot < EQUIPMENT_SLOT_END; Slot++)
            {
                if (Item* newItem = player->GetItemByPos(INVENTORY_SLOT_BAG_0, Slot))
                {
                    if (newItem->DeleteFakeEntry() && !removed)
                        removed = true;
                }
            }
            if (removed)
                session->SendAreaTriggerMessage(session->GetSkyFireString(LANG_REM_TRANSMOGRIFICATIONS_ITEMS));
            else
                session->SendNotification(session->GetSkyFireString(LANG_ERR_NO_TRANSMOGRIFICATIONS));
            OnGossipHello(player, creature);
        } break;
    case EQUIPMENT_SLOT_END+3: // Remove Transmogrification from single item
        {
            if (Item* newItem = player->GetItemByPos(INVENTORY_SLOT_BAG_0, uiAction))
            {
                if (newItem->DeleteFakeEntry())
                    session->SendAreaTriggerMessage(session->GetSkyFireString(LANG_REM_TRANSMOGRIFICATION_ITEM), GetSlotName(uiAction, session));
                else
                    session->SendNotification(session->GetSkyFireString(LANG_ERR_NO_TRANSMOGRIFICATION), GetSlotName(uiAction, session));
            }
            OnGossipSelect(player, creature, EQUIPMENT_SLOT_END, uiAction);
        } break;
    default: // Transmogrify
        {
            uint32 lowGUID = player->GetGUIDLow();
            if (Item* oldItem = player->GetItemByPos(INVENTORY_SLOT_BAG_0, sender))
            {
                if (_items[lowGUID].find(uiAction) != _items[lowGUID].end() && _items[lowGUID][uiAction]->IsInWorld())
                {
                    Item* newItem = _items[lowGUID][uiAction];
                    if (newItem->GetOwnerGUID() == player->GetGUIDLow() && (newItem->IsInBag() || newItem->GetBagSlot() == INVENTORY_SLOT_BAG_0) && player->SuitableForTransmogrification(oldItem, newItem) == ERR_FAKE_OK)
                    {
#if (GOLD_COST)
                        player->ModifyMoney(-1*GetFakePrice(oldItem)); // take cost
#endif
                        oldItem->SetFakeEntry(newItem->GetEntry());
                        newItem->SetBinding(true);
                        session->SendAreaTriggerMessage(session->GetSkyFireString(LANG_ITEM_TRANSMOGRIFIED), GetSlotName(sender, session));
                    }
                    else
                        session->SendNotification(session->GetSkyFireString(LANG_ERR_NO_ITEM_SUITABLE));
                }
                else
                    session->SendNotification(session->GetSkyFireString(LANG_ERR_NO_ITEM_EXISTS));
            }
            else
                session->SendNotification(session->GetSkyFireString(LANG_ERR_EQUIP_SLOT_EMPTY));
            _items[lowGUID].clear();
            OnGossipSelect(player, creature, EQUIPMENT_SLOT_END, sender);
        } break;
    }
    return true;
}

#if (GOLD_COST)
    uint32 GetFakePrice(Item* item)
    {
        uint32 sellPrice = item->GetProto()->SellPrice;
        uint32 minPrice = item->GetProto()->RequiredLevel * 1176;
        if (sellPrice < minPrice)
            sellPrice = minPrice;
        return sellPrice;
    }
#endif

void AddSC_npc_transmogrify()
{
    Script *newscript;

    newscript = new Script;
    newscript->Name = "npc_transmogrify";
    newscript->pGossipHello  = &OnGossipHello;
    newscript->pGossipSelect = &OnGossipSelect;
    newscript->RegisterSelf();
}

